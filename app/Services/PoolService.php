<?php

namespace App\Services;

use App\Events\PoolOrderAdded;
use App\Events\PoolOrderAssigned;
use App\Models\Order;
use App\Models\Courier;
use App\Models\Branch;
use App\Models\BranchSetting;
use App\Notifications\NewPoolOrderNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PoolService
{
    public function __construct(
        private CourierAssignmentService $courierService,
        private AIOrderDistributionService $aiService,
        private CustomerNotificationService $customerNotificationService
    ) {}

    /**
     * Add an order to the pool
     */
    public function addToPool(Order $order): void
    {
        $order->update(['pool_entered_at' => now()]);

        // Broadcast event for real-time updates
        event(new PoolOrderAdded($order));

        // Notify couriers if enabled
        $settings = $this->getPoolSettings($order->branch_id);
        if ($settings && $settings->pool_notify_couriers) {
            $this->notifyCouriers($order);
        }

        Log::info('Order added to pool', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);
    }

    /**
     * Accept an order from the pool (courier manually accepts)
     * Uses database-level pessimistic locking to prevent race conditions
     *
     * @return array{success: bool, code: string, message: string, order?: Order}
     */
    public function acceptFromPool(Order $order, Courier $courier): array
    {
        try {
            return DB::transaction(function () use ($order, $courier) {
                // Pessimistic lock ile siparişi yeniden oku (MySQL FOR UPDATE)
                $order = Order::where('id', $order->id)
                    ->lockForUpdate()
                    ->first();

                // Double-check: Sipariş hala havuzda mı?
                if (!$order || !$order->isInPool()) {
                    return [
                        'success' => false,
                        'code' => 'ORDER_TAKEN',
                        'message' => __('messages.error.order_already_taken'),
                    ];
                }

                // Kurye kapasitesini de kilitle ve kontrol et
                $courier = Courier::where('id', $courier->id)
                    ->lockForUpdate()
                    ->first();

                if (!$courier) {
                    return [
                        'success' => false,
                        'code' => 'COURIER_NOT_FOUND',
                        'message' => __('messages.error.courier_not_found'),
                    ];
                }

                if ($courier->active_orders_count >= Courier::MAX_ACTIVE_ORDERS) {
                    return [
                        'success' => false,
                        'code' => 'LIMIT_REACHED',
                        'message' => __('messages.error.courier_max_orders'),
                    ];
                }

                // Atomic UPDATE - WHERE koşulları ile güvenlik
                $affected = Order::where('id', $order->id)
                    ->whereNull('courier_id')
                    ->whereNotNull('pool_entered_at')
                    ->where('status', Order::STATUS_READY)
                    ->update([
                        'courier_id' => $courier->id,
                        'pool_entered_at' => null,
                        'courier_assigned_at' => now(),
                    ]);

                // Eğer 0 satır etkilendiyse, sipariş zaten alınmış
                if ($affected === 0) {
                    return [
                        'success' => false,
                        'code' => 'ORDER_TAKEN',
                        'message' => __('messages.error.order_already_taken'),
                    ];
                }

                // Kurye istatistiklerini güncelle
                $courier->incrementActiveOrders();

                // Fresh order data
                $order = $order->fresh();

                // Broadcast event for real-time updates (diğer kuryelerin ekranından kaldır)
                event(new PoolOrderAssigned($order, $courier));

                // Müşteriye kurye atandı bildirimi gönder
                $this->sendCourierAssignedNotification($order);

                Log::info('Order accepted from pool with atomic lock', [
                    'order_id' => $order->id,
                    'courier_id' => $courier->id,
                ]);

                return [
                    'success' => true,
                    'code' => 'SUCCESS',
                    'message' => __('messages.success.order_accepted'),
                    'order' => $order,
                ];
            }, 3); // 3 retry on deadlock

        } catch (\Exception $e) {
            Log::error('Pool accept failed', [
                'order_id' => $order->id,
                'courier_id' => $courier->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'code' => 'ERROR',
                'message' => __('messages.error.generic'),
            ];
        }
    }

    /**
     * Send courier assigned notification to customer
     */
    protected function sendCourierAssignedNotification(Order $order): void
    {
        try {
            $this->customerNotificationService->sendCourierAssignedNotification($order);
        } catch (\Exception $e) {
            Log::warning('Courier assigned notification failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get all orders currently in the pool
     */
    public function getPoolOrders(?int $branchId = null): Collection
    {
        $query = Order::inPool()
            ->with(['customer', 'branch'])
            ->orderBy('pool_entered_at', 'asc');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->get();
    }

    /**
     * Get pool orders count
     */
    public function getPoolOrdersCount(?int $branchId = null): int
    {
        $query = Order::inPool();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->count();
    }

    /**
     * Process orders that have exceeded their wait time
     * Returns the number of orders auto-assigned
     */
    public function processTimeoutOrders(): int
    {
        $assigned = 0;

        // Get all branches with pool enabled and auto_assign enabled
        $branchSettings = BranchSetting::where('pool_enabled', true)
            ->where('pool_auto_assign', true)
            ->get();

        foreach ($branchSettings as $settings) {
            $waitTime = $settings->pool_wait_time ?? 5;

            // Get timeout orders for this branch
            $orders = Order::inPool()
                ->where('branch_id', $settings->branch_id)
                ->where('pool_entered_at', '<=', now()->subMinutes($waitTime))
                ->get();

            foreach ($orders as $order) {
                $success = $this->autoAssignOrder($order, $settings);
                if ($success) {
                    $assigned++;
                }
            }
        }

        if ($assigned > 0) {
            Log::info('Pool timeout orders auto-assigned', ['count' => $assigned]);
        }

        return $assigned;
    }

    /**
     * Auto-assign an order to the best available courier
     */
    protected function autoAssignOrder(Order $order, BranchSetting $settings): bool
    {
        // AI dağıtım modunu kontrol et
        $useAI = $settings->pool_ai_distribution ?? true;

        if ($useAI) {
            // AI tabanlı dağıtım
            $courier = $this->aiService->findBestCourier($order);
        } else {
            // Basit mesafe bazlı dağıtım
            $courier = $this->courierService->findBestCourier(
                $order->lat,
                $order->lng
            );
        }

        if (!$courier) {
            Log::warning('No available courier for pool order', [
                'order_id' => $order->id,
                'ai_mode' => $useAI,
            ]);
            return false;
        }

        // Assign courier
        $order->update([
            'courier_id' => $courier->id,
            'courier_assigned_at' => now(),
            'pool_entered_at' => null,
        ]);

        $courier->incrementActiveOrders();

        // Broadcast event for real-time updates
        event(new PoolOrderAssigned($order, $courier));

        // Müşteriye kurye atandı bildirimi gönder
        $this->sendCourierAssignedNotification($order->fresh());

        Log::info('Order auto-assigned from pool', [
            'order_id' => $order->id,
            'courier_id' => $courier->id,
            'ai_mode' => $useAI,
        ]);

        return true;
    }

    /**
     * Notify available couriers about a new pool order
     */
    public function notifyCouriers(Order $order): void
    {
        $couriers = $this->courierService->getAvailableCouriers();

        foreach ($couriers as $courier) {
            if ($courier->canReceiveNotification()) {
                try {
                    $courier->notify(new NewPoolOrderNotification($order));
                } catch (\Exception $e) {
                    Log::error('Failed to notify courier about pool order', [
                        'courier_id' => $courier->id,
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Check if pool is enabled for a branch
     */
    public function isPoolEnabled(?int $branchId = null): bool
    {
        $settings = $this->getPoolSettings($branchId);
        return $settings && $settings->pool_enabled;
    }

    /**
     * Get pool settings for a branch
     */
    public function getPoolSettings(?int $branchId = null): ?BranchSetting
    {
        if (!$branchId) {
            // Get parent branch settings
            $parentBranch = Branch::whereNull('parent_id')->first();
            $branchId = $parentBranch?->id;
        }

        if (!$branchId) {
            return null;
        }

        return BranchSetting::getOrCreateForBranch($branchId);
    }

    /**
     * Get pool statistics
     */
    public function getPoolStats(?int $branchId = null): array
    {
        $orders = $this->getPoolOrders($branchId);
        $settings = $this->getPoolSettings($branchId);
        $waitTime = $settings?->pool_wait_time ?? 5;

        $timeoutOrders = $orders->filter(function ($order) use ($waitTime) {
            return $order->poolWaitingMinutes() >= $waitTime;
        });

        // Get available couriers count
        $availableCouriers = Courier::where('status', Courier::STATUS_AVAILABLE)
            ->orWhere(function ($q) {
                $q->where('status', Courier::STATUS_BUSY)
                  ->where('active_orders_count', '<', Courier::MAX_ACTIVE_ORDERS);
            })
            ->get()
            ->filter(fn($c) => $c->isOnShift())
            ->count();

        return [
            'total_pool' => $orders->count(),
            'total_in_pool' => $orders->count(),
            'timeout_count' => $timeoutOrders->count(),
            'timeout_orders' => $timeoutOrders->count(),
            'oldest_waiting_minutes' => $orders->isNotEmpty()
                ? $orders->first()->poolWaitingMinutes()
                : 0,
            'avg_wait_time' => $orders->isNotEmpty()
                ? (int) $orders->avg(fn($o) => $o->poolWaitingMinutes())
                : 0,
            'average_waiting_minutes' => $orders->isNotEmpty()
                ? (int) $orders->avg(fn($o) => $o->poolWaitingMinutes())
                : 0,
            'available_couriers' => $availableCouriers,
            'pool_enabled' => $settings?->pool_enabled ?? false,
            'pool_wait_time' => $waitTime,
            'pool_auto_assign' => $settings?->pool_auto_assign ?? false,
        ];
    }
}
