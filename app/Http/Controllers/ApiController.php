<?php

namespace App\Http\Controllers;

use App\Helpers\PrivacyHelper;
use App\Models\Courier;
use App\Models\Order;
use App\Models\PricingPolicy;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * API Controller - Harita ve Arama Endpoint'leri
 *
 * GÜVENLİK NOTLARI:
 * - Tüm endpoint'ler yetkilendirme kontrolü gerektirir
 * - Kişisel veriler (telefon, adres, TC) maskelenir
 * - Tüm veri erişimleri loglanır
 *
 * KVKK Uyumu:
 * - Madde 4: Veri minimizasyonu
 * - Madde 12: Veri güvenliği
 *
 * @package App\Http\Controllers
 * @version 2.0.0 - Güvenlik revizyonu
 */
class ApiController extends Controller
{
    /**
     * Kurye arama (harita ve otomatik tamamlama)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchCouriers(Request $request): JsonResponse
    {
        // Yetkilendirme kontrolü
        if (!$this->canAccessCourierData()) {
            AuditLogService::logPermissionDenied('couriers', 'search');
            return response()->json(['error' => 'Yetkisiz erişim'], 403);
        }

        $query = $request->input('q', '');
        $status = $request->input('status');

        // Validasyon
        $request->validate([
            'q' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:available,busy,offline,on_break',
        ]);

        $couriers = Courier::query()
            ->when($query, function ($q) use ($query) {
                $q->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('vehicle_plate', 'like', "%{$query}%");
                    // NOT: Telefon araması kaldırıldı - KVKK uyumu
                });
            })
            ->when($status, function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get()
            ->map(fn($c) => $this->formatCourierForList($c));

        // Veri erişim logu
        AuditLogService::logDataAccess('Courier', 'search', 'list');

        return response()->json($couriers);
    }

    /**
     * Sipariş arama
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchOrders(Request $request): JsonResponse
    {
        // Yetkilendirme kontrolü
        if (!$this->canAccessOrderData()) {
            AuditLogService::logPermissionDenied('orders', 'search');
            return response()->json(['error' => 'Yetkisiz erişim'], 403);
        }

        $query = $request->input('q', '');
        $status = $request->input('status');

        // Validasyon
        $request->validate([
            'q' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:pending,preparing,ready,assigned,picked_up,delivering,delivered,cancelled,active',
        ]);

        $orders = Order::query()
            ->when($query, function ($q) use ($query) {
                $q->where(function ($q) use ($query) {
                    // Sadece sipariş numarası ve müşteri adıyla arama
                    // Telefon ve adres araması kaldırıldı - KVKK uyumu
                    $q->where('order_number', 'like', "%{$query}%")
                      ->orWhere('customer_name', 'like', "%{$query}%");
                });
            })
            ->when($status, function ($q) use ($status) {
                if ($status === 'active') {
                    $q->whereNotIn('status', ['delivered', 'cancelled']);
                } else {
                    $q->where('status', $status);
                }
            })
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->with('courier')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($o) => $this->formatOrderForList($o));

        // Veri erişim logu
        AuditLogService::logDataAccess('Order', 'search', 'list');

        return response()->json($orders);
    }

    /**
     * Kurye detayları (harita popup)
     *
     * @param Courier $courier
     * @return JsonResponse
     */
    public function showCourier(Courier $courier): JsonResponse
    {
        // Yetkilendirme kontrolü
        if (!$this->canAccessCourierData()) {
            AuditLogService::logPermissionDenied('couriers', 'view');
            return response()->json(['error' => 'Yetkisiz erişim'], 403);
        }

        $todayOrders = $courier->orders()
            ->whereDate('created_at', today())
            ->get();

        $activeOrders = $courier->orders()
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->get();

        // Veri erişim logu
        AuditLogService::logDataAccess('Courier', $courier->id, 'detail');

        // Kullanıcının rolüne göre hassas veri görünürlüğü
        $showSensitiveData = $this->canViewSensitiveData();

        return response()->json([
            'id' => $courier->id,
            'name' => $courier->name,
            // Telefon maskeleme - sadece yetkili kullanıcılar tam görebilir
            'phone' => $showSensitiveData
                ? $courier->phone
                : PrivacyHelper::maskPhone($courier->phone),
            // Email maskeleme
            'email' => $showSensitiveData
                ? $courier->email
                : PrivacyHelper::maskEmail($courier->email),
            // TC Kimlik numarası API'den tamamen kaldırıldı (KVKK)
            // 'tc_no' => KALDIRILDI - Bu veri API üzerinden paylaşılmamalı
            'vehicle_type' => $courier->vehicle_type,
            'vehicle_plate' => $courier->vehicle_plate,
            'status' => $courier->status,
            'status_label' => $courier->getStatusLabel(),
            'lat' => (float) $courier->lat,
            'lng' => (float) $courier->lng,
            'shifts' => $courier->shifts,
            'break_durations' => $courier->break_durations,
            'is_on_shift' => $courier->isOnShift(),
            'active_orders_count' => $activeOrders->count(),
            'today_deliveries' => $todayOrders->where('status', 'delivered')->count(),
            'today_earnings' => $todayOrders->where('status', 'delivered')->sum(
                fn($order) => PricingPolicy::calculateCourierEarnings($order)
            ),
            'active_orders' => $activeOrders->map(fn($o) => $this->formatOrderForList($o)),
        ]);
    }

    /**
     * Harita verileri (kuryeler ve siparişler)
     *
     * @return JsonResponse
     */
    public function mapData(): JsonResponse
    {
        // Yetkilendirme kontrolü
        if (!$this->canAccessMapData()) {
            AuditLogService::logPermissionDenied('map', 'view');
            return response()->json(['error' => 'Yetkisiz erişim'], 403);
        }

        $couriers = Courier::whereNotNull('lat')
            ->whereNotNull('lng')
            ->get()
            ->map(fn($c) => $this->formatCourierForMap($c));

        $orders = Order::whereNotIn('status', ['delivered', 'cancelled'])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->with('courier')
            ->get()
            ->map(fn($o) => $this->formatOrderForMap($o));

        // Pool orders with waiting time info
        $poolOrders = Order::inPool()
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->with('courier')
            ->orderBy('pool_entered_at', 'asc')
            ->get()
            ->map(fn($o) => $this->formatPoolOrderForMap($o));

        $stats = [
            'pending' => Order::where('status', 'pending')->count(),
            'active' => Order::whereNotIn('status', ['delivered', 'cancelled'])->count(),
            'pool' => $poolOrders->count(),
            'cancelled' => Order::where('status', 'cancelled')->whereDate('created_at', today())->count(),
        ];

        // Veri erişim logu
        AuditLogService::logDataAccess('Map', 'all', 'view');

        return response()->json([
            'couriers' => $couriers,
            'orders' => $orders,
            'pool_orders' => $poolOrders,
            'stats' => $stats,
        ]);
    }

    // =========================================================================
    // PRIVATE HELPER METHODS
    // =========================================================================

    /**
     * Kurye verilerini liste için formatla (maskelenmiş)
     */
    private function formatCourierForList(Courier $courier): array
    {
        return [
            'id' => $courier->id,
            'name' => $courier->name,
            'phone' => PrivacyHelper::maskPhone($courier->phone),
            'lat' => (float) $courier->lat,
            'lng' => (float) $courier->lng,
            'status' => $courier->status,
            'status_label' => $courier->getStatusLabel(),
            'vehicle_plate' => $courier->vehicle_plate,
            'active_orders_count' => $courier->active_orders_count,
        ];
    }

    /**
     * Kurye verilerini harita için formatla
     */
    private function formatCourierForMap(Courier $courier): array
    {
        return [
            'id' => $courier->id,
            'name' => $courier->name,
            // Haritada telefon gösterilmez
            'lat' => (float) $courier->lat,
            'lng' => (float) $courier->lng,
            'status' => $courier->status,
            'vehicle_plate' => $courier->vehicle_plate,
            'active_orders_count' => $courier->active_orders_count,
        ];
    }

    /**
     * Sipariş verilerini liste için formatla (maskelenmiş)
     */
    private function formatOrderForList(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            // Müşteri adı kısaltılmış
            'customer_name' => PrivacyHelper::maskName($order->customer_name),
            // Telefon tamamen maskelenmiş
            'customer_phone' => PrivacyHelper::maskPhone($order->customer_phone),
            // Adres sadece ilçe görünür
            'customer_address' => PrivacyHelper::maskAddress($order->customer_address),
            'lat' => (float) $order->lat,
            'lng' => (float) $order->lng,
            'status' => $order->status,
            'status_label' => $order->getStatusLabel(),
            'total' => $order->total,
            'courier_name' => $order->courier?->name,
            'created_at' => $order->created_at->diffForHumans(),
        ];
    }

    /**
     * Sipariş verilerini harita için formatla
     */
    private function formatOrderForMap(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            // Haritada müşteri bilgisi minimum
            'customer_name' => PrivacyHelper::maskName($order->customer_name),
            'customer_address' => PrivacyHelper::maskAddress($order->customer_address),
            'lat' => (float) $order->lat,
            'lng' => (float) $order->lng,
            'status' => $order->status,
            'total' => $order->total,
            'courier_id' => $order->courier_id,
            'courier_name' => $order->courier?->name,
        ];
    }

    /**
     * Pool sipariş verilerini harita için formatla
     */
    private function formatPoolOrderForMap(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => PrivacyHelper::maskName($order->customer_name),
            'customer_address' => PrivacyHelper::maskAddress($order->customer_address),
            'lat' => (float) $order->lat,
            'lng' => (float) $order->lng,
            'status' => $order->status,
            'total' => $order->total,
            'pool_entered_at' => $order->pool_entered_at?->toIso8601String(),
            'waiting_seconds' => $order->poolWaitingSeconds(),
            'waiting_minutes' => $order->poolWaitingMinutes(),
            'is_timeout' => $order->poolWaitingMinutes() >= 5, // Default 5 min timeout
        ];
    }

    // =========================================================================
    // AUTHORIZATION CHECKS
    // =========================================================================

    /**
     * Kurye verilerine erişim yetkisi kontrolü
     */
    private function canAccessCourierData(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        $roles = $user->roles ?? [];

        // Admin, bayi ve işletme rolleri erişebilir
        return !empty(array_intersect($roles, ['admin', 'bayi', 'isletme']));
    }

    /**
     * Sipariş verilerine erişim yetkisi kontrolü
     */
    private function canAccessOrderData(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        $roles = $user->roles ?? [];

        // Admin, bayi ve işletme rolleri erişebilir
        return !empty(array_intersect($roles, ['admin', 'bayi', 'isletme']));
    }

    /**
     * Harita verilerine erişim yetkisi kontrolü
     */
    private function canAccessMapData(): bool
    {
        return $this->canAccessCourierData() && $this->canAccessOrderData();
    }

    /**
     * Hassas verileri maskelenmeden görme yetkisi
     */
    private function canViewSensitiveData(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        $roles = $user->roles ?? [];

        // Sadece admin ve bayi tam veriyi görebilir
        return !empty(array_intersect($roles, ['admin', 'bayi']));
    }
}
