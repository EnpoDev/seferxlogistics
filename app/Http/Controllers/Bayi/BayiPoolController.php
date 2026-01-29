<?php

namespace App\Http\Controllers\Bayi;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\Order;
use App\Services\PoolService;
use Illuminate\Http\Request;

class BayiPoolController extends Controller
{
    public function __construct(
        private PoolService $poolService
    ) {}

    public function poolDashboard()
    {
        $poolStats = $this->poolService->getPoolStats();
        $poolOrders = $this->poolService->getPoolOrders();

        // Müsait kuryeler
        $availableCouriers = Courier::where('status', Courier::STATUS_AVAILABLE)
            ->orWhere(function ($q) {
                $q->where('status', Courier::STATUS_BUSY)
                  ->where('active_orders_count', '<', Courier::MAX_ACTIVE_ORDERS);
            })
            ->get()
            ->filter(fn($c) => $c->isOnShift());

        // Pool ayarları
        $branch = \App\Models\Branch::whereNull('parent_id')->first();
        $settings = $branch ? \App\Models\BranchSetting::getOrCreateForBranch($branch->id) : null;

        // Son 24 saat pool istatistikleri (SQLite uyumlu)
        $recentPoolOrders = Order::where('pool_entered_at', '>=', now()->subHours(24))->get();
        $last24Hours = (object) [
            'total_pool_orders' => $recentPoolOrders->count(),
            'assigned_orders' => $recentPoolOrders->whereNotNull('courier_id')->count(),
            'avg_wait_time' => $recentPoolOrders->avg(function ($order) {
                if ($order->pool_entered_at) {
                    $endTime = $order->updated_at ?? now();
                    return $order->pool_entered_at->diffInMinutes($endTime);
                }
                return 0;
            }),
        ];

        return view('bayi.pool-dashboard', compact(
            'poolStats',
            'poolOrders',
            'availableCouriers',
            'settings',
            'last24Hours'
        ));
    }

    public function poolAssign(Request $request, Order $order)
    {
        $validated = $request->validate([
            'courier_id' => 'required|exists:couriers,id',
        ]);

        $courier = Courier::findOrFail($validated['courier_id']);

        // Kurye müsait mi kontrol et
        if ($courier->active_orders_count >= Courier::MAX_ACTIVE_ORDERS) {
            return response()->json([
                'success' => false,
                'message' => 'Kurye maksimum sipariş limitine ulaşmış.',
            ], 400);
        }

        // Sipariş havuzda mı kontrol et
        if (!$order->isInPool()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu sipariş havuzda değil.',
            ], 400);
        }

        // Ata
        $order->update([
            'courier_id' => $courier->id,
            'pool_entered_at' => null,
            'courier_assigned_at' => now(),
        ]);

        $courier->incrementActiveOrders();

        // Müşteriye kurye atandı bildirimi gönder
        try {
            $notificationService = new \App\Services\CustomerNotificationService();
            $notificationService->sendCourierAssignedNotification($order->fresh());
        } catch (\Exception $e) {
            \Log::warning('Courier assigned notification failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Sipariş {$courier->name}'a atandı.",
        ]);
    }

    public function poolAutoAssign(Order $order)
    {
        if (!$order->isInPool()) {
            return response()->json([
                'success' => false,
                'message' => 'Bu sipariş havuzda değil.',
            ], 400);
        }

        // AI tabanlı dağıtım servisi kullan
        $aiService = new \App\Services\AIOrderDistributionService();
        $courier = $aiService->findBestCourier($order);

        if (!$courier) {
            return response()->json([
                'success' => false,
                'message' => 'Uygun kurye bulunamadı.',
            ], 400);
        }

        // Kurye önerilerini de döndür
        $suggestions = $aiService->getSuggestedCouriers($order, 3);

        $order->update([
            'courier_id' => $courier->id,
            'courier_assigned_at' => now(),
            'pool_entered_at' => null,
        ]);

        $courier->incrementActiveOrders();

        return response()->json([
            'success' => true,
            'message' => "Sipariş AI ile {$courier->name}'a atandı.",
            'courier' => [
                'id' => $courier->id,
                'name' => $courier->name,
            ],
            'ai_score' => $suggestions->first()['score'] ?? null,
        ]);
    }

    public function poolBulkAssign(Request $request)
    {
        // order_ids opsiyonel - verilmezse havuzdaki tüm siparişleri ata
        $orderIds = $request->input('order_ids');

        if ($orderIds) {
            $orders = Order::whereIn('id', $orderIds)->get();
        } else {
            // Havuzdaki tüm siparişleri al
            $orders = Order::inPool()->get();
        }

        // AI tabanlı dağıtım servisi kullan
        $aiService = new \App\Services\AIOrderDistributionService();
        $assigned = 0;
        $failed = 0;
        $assignments = [];

        foreach ($orders as $order) {
            if (!$order->isInPool()) {
                $failed++;
                continue;
            }

            $courier = $aiService->findBestCourier($order);

            if (!$courier) {
                $failed++;
                continue;
            }

            $order->update([
                'courier_id' => $courier->id,
                'courier_assigned_at' => now(),
                'pool_entered_at' => null,
            ]);

            $courier->incrementActiveOrders();
            $assigned++;
            $assignments[] = [
                'order_number' => $order->order_number,
                'courier_name' => $courier->name,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => "{$assigned} sipariş AI ile atandı, {$failed} başarısız.",
            'assigned_count' => $assigned,
            'failed_count' => $failed,
            'assignments' => $assignments,
        ]);
    }

    public function poolStats()
    {
        $poolStats = $this->poolService->getPoolStats();
        $poolOrders = $this->poolService->getPoolOrders();

        // Müsait kuryeler
        $availableCouriers = Courier::where('status', Courier::STATUS_AVAILABLE)
            ->orWhere(function ($q) {
                $q->where('status', Courier::STATUS_BUSY)
                  ->where('active_orders_count', '<', Courier::MAX_ACTIVE_ORDERS);
            })
            ->get()
            ->filter(fn($c) => $c->isOnShift());

        return response()->json([
            'stats' => $poolStats,
            'pool_orders' => $poolOrders->map(fn($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'customer_name' => $o->customer_name,
                'customer_address' => $o->customer_address,
                'customer_phone' => $o->customer_phone,
                'total' => $o->total,
                'waiting_minutes' => $o->poolWaitingMinutes() ?? 0,
                'is_timeout' => ($o->poolWaitingMinutes() ?? 0) >= 5,
                'created_at' => $o->created_at->diffForHumans(),
            ]),
            'couriers' => $availableCouriers->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'vehicle_plate' => $c->vehicle_plate,
                'status' => $c->status,
                'status_label' => $c->getStatusLabel(),
                'active_orders' => $c->active_orders_count ?? 0,
            ]),
        ]);
    }
}
