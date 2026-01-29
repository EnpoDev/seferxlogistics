<?php

namespace App\Http\Controllers\Kurye;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\Order;
use App\Services\CustomerNotificationService;
use App\Services\ProofOfDeliveryService;
use App\Services\RouteOptimizationService;
use App\Services\GeofencingService;
use App\Services\PoolService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KuryeAppController extends Controller
{
    public function __construct(
        private GeofencingService $geofencingService,
        private RouteOptimizationService $routeOptimizationService,
        private PoolService $poolService,
        private CustomerNotificationService $customerNotificationService
    ) {}

    protected function courier(): Courier
    {
        return Auth::guard('courier')->user();
    }

    public function dashboard()
    {
        $courier = $this->courier();
        
        // Active orders assigned to this courier
        $activeOrders = Order::with(['items', 'restaurant', 'branch'])
            ->where('courier_id', $courier->id)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        // Today's stats
        $todayDelivered = Order::where('courier_id', $courier->id)
            ->where('status', 'delivered')
            ->whereDate('updated_at', today())
            ->count();
        
        $todayEarnings = Order::where('courier_id', $courier->id)
            ->where('status', 'delivered')
            ->whereDate('updated_at', today())
            ->sum('delivery_fee') ?? 0;
        
        return view('kurye.dashboard', compact('courier', 'activeOrders', 'todayDelivered', 'todayEarnings'));
    }

    public function orders()
    {
        $courier = $this->courier();
        
        $activeOrders = Order::with(['items', 'restaurant', 'branch'])
            ->where('courier_id', $courier->id)
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('kurye.orders', compact('courier', 'activeOrders'));
    }

    public function orderDetail(Order $order)
    {
        $courier = $this->courier();
        
        // Ensure the order belongs to this courier
        if ($order->courier_id !== $courier->id) {
            abort(403, 'Bu siparişe erişim yetkiniz yok.');
        }
        
        return view('kurye.order-detail', compact('courier', 'order'));
    }

    public function updateOrderStatus(Request $request, Order $order)
    {
        $courier = $this->courier();

        if ($order->courier_id !== $courier->id) {
            return response()->json(['success' => false, 'message' => __('messages.error.unauthorized')], 403);
        }

        $request->validate([
            'status' => ['required', 'in:picked_up,on_way,delivered,cancelled'],
        ]);

        $displayStatus = $request->status;
        $currentDisplayStatus = $order->display_status;

        // Display status transition validation
        $allowedTransitions = [
            'assigned' => ['picked_up', 'cancelled'],
            'picked_up' => ['on_way', 'cancelled'],
            'on_way' => ['delivered', 'cancelled'],
        ];

        if (!isset($allowedTransitions[$currentDisplayStatus]) ||
            !in_array($displayStatus, $allowedTransitions[$currentDisplayStatus])) {
            return response()->json([
                'success' => false,
                'message' => __('messages.error.invalid_status_change') . ' (' . $currentDisplayStatus . ')'
            ], 400);
        }

        $updateData = [];

        // Map display status to DB status and timestamps
        switch ($displayStatus) {
            case 'picked_up':
                // Courier picked up the order
                $updateData['status'] = Order::STATUS_ON_DELIVERY;
                $updateData['picked_up_at'] = now();
                break;

            case 'on_way':
                // Courier is on the way (status stays on_delivery, just update timestamp)
                $updateData['on_way_at'] = now();
                break;

            case 'delivered':
                $updateData['status'] = Order::STATUS_DELIVERED;
                $updateData['delivered_at'] = now();
                break;

            case 'cancelled':
                $updateData['status'] = Order::STATUS_CANCELLED;
                $updateData['cancelled_at'] = now();
                break;
        }

        $order->update($updateData);

        // If delivered, update courier stats
        if ($displayStatus === 'delivered') {
            $deliveryMinutes = $order->created_at->diffInMinutes(now());
            $courier->recordDelivery($deliveryMinutes);
            $courier->decrementActiveOrders();

            // Nakit ödemeli siparişlerde kuryenin bakiyesini güncelle
            $order->updateCourierCashBalance();

            // Müşteri istatistiklerini güncelle
            $order->updateCustomerStats();
        }

        // If cancelled, decrement active orders
        if ($displayStatus === 'cancelled') {
            $courier->decrementActiveOrders();
        }

        // Müşteriye bildirim gönder (picked_up veya delivered)
        if (in_array($displayStatus, ['picked_up', 'delivered'])) {
            try {
                // picked_up display status -> on_delivery DB status için sendStatusNotification kullan
                $statusForNotification = $displayStatus === 'picked_up' ? 'on_delivery' : $displayStatus;
                $this->customerNotificationService->sendStatusNotification($order, $statusForNotification);
            } catch (\Exception $e) {
                \Log::error(__('messages.error.customer_notification_failed'), ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => __('messages.success.order_status_updated'),
            'new_status' => $displayStatus,
            'display_status' => $order->display_status,
            'status_label' => $order->getStatusLabel(),
        ]);
    }

    public function history(Request $request)
    {
        $courier = $this->courier();
        
        $orders = Order::where('courier_id', $courier->id)
            ->whereIn('status', ['delivered', 'cancelled'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);
        
        return view('kurye.history', compact('courier', 'orders'));
    }

    public function profile()
    {
        $courier = $this->courier();
        
        // Monthly stats
        $monthlyDelivered = Order::where('courier_id', $courier->id)
            ->where('status', 'delivered')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();
        
        $monthlyEarnings = Order::where('courier_id', $courier->id)
            ->where('status', 'delivered')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->sum('delivery_fee') ?? 0;
        
        return view('kurye.profile', compact('courier', 'monthlyDelivered', 'monthlyEarnings'));
    }

    public function updateStatus(Request $request)
    {
        $courier = $this->courier();
        
        $request->validate([
            'status' => ['required', 'in:available,on_break,offline'],
        ]);
        
        $courier->update(['status' => $request->status]);
        
        return response()->json([
            'success' => true,
            'message' => __('messages.success.status_updated'),
            'status' => $courier->status,
            'status_label' => $courier->getStatusLabel(),
        ]);
    }

    public function updateLocation(Request $request)
    {
        $courier = $this->courier();

        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $courier->update([
            'lat' => $request->lat,
            'lng' => $request->lng,
        ]);

        // Geofencing kontrolü yap
        $geofenceResult = $this->geofencingService->processLocationUpdate(
            $courier,
            (float) $request->lat,
            (float) $request->lng
        );

        return response()->json([
            'success' => true,
            'message' => __('messages.success.location_updated'),
            'geofence' => $geofenceResult,
        ]);
    }

    public function updateDeviceToken(Request $request)
    {
        $courier = $this->courier();

        $request->validate([
            'device_token' => ['required', 'string'],
        ]);

        $courier->update(['device_token' => $request->device_token]);

        return response()->json([
            'success' => true,
            'message' => __('messages.success.device_token_updated'),
        ]);
    }

    /**
     * Pil durumu güncelle
     */
    public function updateBattery(Request $request)
    {
        $courier = $this->courier();

        $request->validate([
            'level' => ['required', 'integer', 'between:0,100'],
            'is_charging' => ['required', 'boolean'],
        ]);

        $courier->update([
            'battery_level' => $request->level,
            'is_charging' => $request->is_charging,
            'battery_updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.success.battery_updated'),
        ]);
    }

    /**
     * Toplu senkronizasyon (offline mod için)
     */
    public function bulkSync(Request $request)
    {
        $courier = $this->courier();
        $results = [
            'status_updates' => ['success' => 0, 'failed' => 0],
            'location' => false,
        ];

        // Durum güncellemeleri
        if ($request->has('status_updates')) {
            foreach ($request->status_updates as $update) {
                try {
                    $order = Order::where('id', $update['order_id'])
                        ->where('courier_id', $courier->id)
                        ->first();

                    if ($order) {
                        $order->update(['status' => $update['status']]);
                        $results['status_updates']['success']++;
                    } else {
                        $results['status_updates']['failed']++;
                    }
                } catch (\Exception $e) {
                    $results['status_updates']['failed']++;
                }
            }
        }

        // Konum güncellemesi (en son konum)
        if ($request->has('location')) {
            $courier->update([
                'lat' => $request->location['lat'],
                'lng' => $request->location['lng'],
            ]);
            $results['location'] = true;

            // Geofencing kontrolü
            $results['geofence'] = $this->geofencingService->processLocationUpdate(
                $courier,
                (float) $request->location['lat'],
                (float) $request->location['lng']
            );
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    // Pool orders - unassigned orders that couriers can accept
    public function pool()
    {
        $courier = $this->courier();

        // Check if courier is available to receive orders
        if (!$courier->isOnShift()) {
            $poolOrders = collect();
            return view('kurye.pool', compact('courier', 'poolOrders'));
        }

        // Use PoolService to get pool orders
        $poolOrders = $this->poolService->getPoolOrders();

        // Filter by courier's zones if they have any assigned
        $courierZoneIds = $courier->zones()->pluck('zones.id');
        if ($courierZoneIds->isNotEmpty()) {
            // Filter orders by zones if needed
            // This requires orders to have zone_id field
        }

        // Calculate distance for each order from courier's current location
        if ($courier->lat && $courier->lng) {
            $poolOrders = $poolOrders->map(function ($order) use ($courier) {
                if ($order->lat && $order->lng) {
                    $order->distance_km = $this->calculateDistance(
                        $courier->lat,
                        $courier->lng,
                        $order->lat,
                        $order->lng
                    );
                } else {
                    $order->distance_km = null;
                }
                return $order;
            })->sortBy(fn($o) => $o->distance_km ?? PHP_INT_MAX);
        }

        // Return JSON if requested
        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'orders' => $poolOrders->map(fn($o) => [
                    'id' => $o->id,
                    'order_number' => $o->order_number,
                    'customer_name' => $o->customer_name,
                    'customer_address' => $o->customer_address,
                    'total' => $o->total,
                    'waiting_minutes' => $o->poolWaitingMinutes() ?? 0,
                    'distance_km' => $o->distance_km ?? null,
                    'created_at' => $o->created_at->diffForHumans(),
                ]),
            ]);
        }

        return view('kurye.pool', compact('courier', 'poolOrders'));
    }

    public function acceptOrder(Order $order)
    {
        $courier = $this->courier();

        // Pre-check: Courier must be on shift (fast fail before atomic operation)
        if (!$courier->isOnShift()) {
            return response()->json([
                'success' => false,
                'code' => 'NOT_ON_SHIFT',
                'message' => __('messages.error.courier_not_on_shift'),
            ], 400);
        }

        // Use PoolService with atomic locking for "first-click-wins"
        $result = $this->poolService->acceptFromPool($order, $courier);

        if (!$result['success']) {
            // Error code'a göre uygun HTTP status döndür
            $statusCode = match ($result['code']) {
                'ORDER_TAKEN' => 409,      // Conflict - sipariş başkası tarafından alındı
                'LIMIT_REACHED' => 429,    // Too Many Requests - limit aşıldı
                'LOCK_FAILED' => 503,      // Service Unavailable - tekrar dene
                default => 400,            // Bad Request
            };

            return response()->json([
                'success' => false,
                'code' => $result['code'],
                'message' => $result['message'],
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'code' => 'SUCCESS',
            'message' => __('messages.success.order_accepted'),
            'redirect' => route('kurye.order.detail', $order),
        ]);
    }

    // Route Optimization Methods

    /**
     * Optimize edilmiş rota sayfası
     */
    public function routePage()
    {
        $courier = $this->courier();

        $activeOrders = Order::with(['branch', 'restaurant'])
            ->where('courier_id', $courier->id)
            ->whereIn('status', [Order::STATUS_READY, Order::STATUS_ON_DELIVERY])
            ->get();

        if ($activeOrders->isEmpty()) {
            return redirect()->route('kurye.dashboard')
                ->with('info', __('messages.error.no_orders_to_optimize'));
        }

        // Optimize route
        $startPoint = $courier->lat && $courier->lng
            ? ['lat' => (float) $courier->lat, 'lng' => (float) $courier->lng]
            : null;

        $optimizedOrders = $this->routeOptimizationService->optimizeRoute($activeOrders, $startPoint);
        $optimizedOrders = $this->routeOptimizationService->estimateDeliveryTimes($optimizedOrders, $startPoint);
        $totalDistance = $this->routeOptimizationService->calculateRouteDistance($optimizedOrders, $startPoint);
        $routeGeoJson = $this->routeOptimizationService->getRouteGeoJson($optimizedOrders, $startPoint);

        return view('kurye.route', compact('courier', 'optimizedOrders', 'totalDistance', 'routeGeoJson', 'startPoint'));
    }

    /**
     * Rota optimizasyonu API
     */
    public function optimizeRoute()
    {
        $courier = $this->courier();

        $result = $this->routeOptimizationService->optimizeCourierRoute($courier);

        return response()->json($result);
    }

    /**
     * Manuel rota sıralaması değiştir
     */
    public function reorderRoute(Request $request)
    {
        $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'integer', 'exists:orders,id'],
        ]);

        $courier = $this->courier();

        // Verify all orders belong to this courier
        $orderIds = $request->order_ids;
        $validOrders = Order::whereIn('id', $orderIds)
            ->where('courier_id', $courier->id)
            ->count();

        if ($validOrders !== count($orderIds)) {
            return response()->json([
                'success' => false,
                'message' => __('messages.error.invalid_order_list'),
            ], 400);
        }

        $reorderedRoute = $this->routeOptimizationService->reorderRoute($orderIds);
        $routeWithTimes = $this->routeOptimizationService->estimateDeliveryTimes($reorderedRoute);

        return response()->json([
            'success' => true,
            'route' => $routeWithTimes->map(fn($order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'sequence' => $order->sequence,
                'estimated_arrival' => $order->estimated_arrival?->format('H:i'),
            ]),
        ]);
    }

    // POD (Proof of Delivery) Methods

    /**
     * Teslimat sayfasını göster (fotoğraf çekme arayüzü)
     */
    public function deliverPage(Order $order)
    {
        $courier = $this->courier();

        if ($order->courier_id !== $courier->id) {
            abort(403, 'Bu siparişe erişim yetkiniz yok.');
        }

        if ($order->status !== Order::STATUS_ON_DELIVERY) {
            return redirect()->route('kurye.order.detail', $order)
                ->with('error', __('messages.error.order_not_ready_for_delivery'));
        }

        return view('kurye.deliver', compact('courier', 'order'));
    }

    /**
     * POD fotoğrafı yükle
     */
    public function uploadPod(Request $request, Order $order)
    {
        $courier = $this->courier();

        if ($order->courier_id !== $courier->id) {
            return response()->json(['success' => false, 'message' => __('messages.error.unauthorized')], 403);
        }

        $request->validate([
            'photo' => ['required', 'image', 'max:10240'], // max 10MB
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $location = null;
        if ($request->has('lat') && $request->has('lng')) {
            $location = [
                'lat' => (float) $request->lat,
                'lng' => (float) $request->lng,
            ];
        }

        try {
            $podService = new ProofOfDeliveryService();
            $result = $podService->uploadPhoto(
                $order,
                $request->file('photo'),
                $location,
                $request->note
            );

            // Kurye istatistiklerini güncelle
            $deliveryMinutes = $order->created_at->diffInMinutes(now());
            $courier->recordDelivery($deliveryMinutes);
            $courier->decrementActiveOrders();

            // Nakit ödemeli siparişlerde kuryenin bakiyesini güncelle
            $order->updateCourierCashBalance();

            // Müşteri istatistiklerini güncelle
            $order->updateCustomerStats();

            // Müşteriye teslimat bildirimi gönder
            try {
                $this->customerNotificationService->sendStatusNotification($order, 'delivered');
            } catch (\Exception $e) {
                \Log::error('Müşteri bildirim hatası (POD)', ['error' => $e->getMessage()]);
            }

            return response()->json([
                'success' => true,
                'message' => __('messages.success.pod_uploaded'),
                'photo_url' => $result['photo_url'],
                'redirect' => route('kurye.dashboard'),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('POD upload error', ['error' => $e->getMessage(), 'order_id' => $order->id]);
            return response()->json([
                'success' => false,
                'message' => __('messages.error.photo_upload_failed'),
            ], 500);
        }
    }

    /**
     * POD bilgisini getir
     */
    public function getPod(Order $order)
    {
        $courier = $this->courier();

        if ($order->courier_id !== $courier->id) {
            return response()->json(['success' => false, 'message' => __('messages.error.unauthorized')], 403);
        }

        $podService = new ProofOfDeliveryService();
        $pod = $podService->getDeliveryProof($order);

        if (!$pod) {
            return response()->json([
                'success' => false,
                'message' => __('messages.error.pod_not_found'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'pod' => $pod,
        ]);
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $latDiff = deg2rad($lat2 - $lat1);
        $lngDiff = deg2rad($lng2 - $lng1);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDiff / 2) * sin($lngDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }
}

