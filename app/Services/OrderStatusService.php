<?php

namespace App\Services;

use App\Events\OrderStatusUpdated;
use App\Models\Courier;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderStatusService
{
    protected CustomerNotificationService $notificationService;

    public function __construct(?CustomerNotificationService $notificationService = null)
    {
        $this->notificationService = $notificationService ?? new CustomerNotificationService();
    }

    /**
     * Assign a courier to an order
     */
    public function assignCourier(Order $order, Courier $courier): bool
    {
        try {
            $oldStatus = $order->status;

            $order->update([
                'courier_id' => $courier->id,
                'courier_assigned_at' => now(),
                'pool_entered_at' => null,
            ]);

            $courier->incrementActiveOrders();

            event(new OrderStatusUpdated($order, $oldStatus));

            // Send notification to customer
            $this->notificationService->sendCourierAssignedNotification($order);

            Log::info('Courier assigned to order', [
                'order_id' => $order->id,
                'courier_id' => $courier->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to assign courier to order', [
                'order_id' => $order->id,
                'courier_id' => $courier->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Unassign courier from an order
     */
    public function unassignCourier(Order $order): bool
    {
        $oldStatus = $order->status;
        $courier = $order->courier;

        $order->update([
            'courier_id' => null,
            'courier_assigned_at' => null,
        ]);

        if ($courier) {
            $courier->decrementActiveOrders();
        }

        event(new OrderStatusUpdated($order, $oldStatus));

        return true;
    }

    /**
     * Mark order as picked up by courier
     */
    public function markPickedUp(Order $order): bool
    {
        $oldStatus = $order->status;

        $order->update([
            'status' => Order::STATUS_ON_DELIVERY,
            'picked_up_at' => now(),
        ]);

        event(new OrderStatusUpdated($order, $oldStatus));

        // Send notification to customer
        $this->notificationService->sendStatusNotification($order, 'picked_up');

        return true;
    }

    /**
     * Mark order as on the way (courier left restaurant)
     */
    public function markOnWay(Order $order): bool
    {
        $oldStatus = $order->status;

        $order->update([
            'on_way_at' => now(),
        ]);

        event(new OrderStatusUpdated($order, $oldStatus));

        // Send notification to customer
        $this->notificationService->sendStatusNotification($order, 'on_way');

        return true;
    }

    /**
     * Mark order as delivered
     */
    public function markDelivered(Order $order): bool
    {
        try {
            $oldStatus = $order->status;

            DB::transaction(function () use ($order) {
                $order->update([
                    'status' => Order::STATUS_DELIVERED,
                    'delivered_at' => now(),
                ]);

                // Update courier cash balance for cash orders
                $this->updateCourierCashBalance($order);

                // Update customer statistics
                if ($order->customer) {
                    $order->customer->updateOrderStats();
                }

                // Decrement courier active orders
                if ($order->courier) {
                    $order->courier->decrementActiveOrders();
                }
            });

            event(new OrderStatusUpdated($order, $oldStatus));

            // Send notification to customer
            $this->notificationService->sendStatusNotification($order, 'delivered');

            Log::info('Order marked as delivered', ['order_id' => $order->id]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to mark order as delivered', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Update courier cash balance for cash payment orders
     */
    public function updateCourierCashBalance(Order $order): void
    {
        // Only for cash payment and delivered orders
        if ($order->payment_method !== Order::PAYMENT_CASH) {
            return;
        }

        if ($order->status !== Order::STATUS_DELIVERED) {
            return;
        }

        if (!$order->courier_id) {
            return;
        }

        $courier = $order->courier;
        if ($courier) {
            $courier->increment('cash_balance', $order->total);

            Log::info('Courier cash balance updated', [
                'courier_id' => $courier->id,
                'order_id' => $order->id,
                'amount' => $order->total,
            ]);
        }
    }

    /**
     * Update order status with validation
     */
    public function updateStatus(Order $order, string $newStatus): bool
    {
        $validTransitions = $this->getValidStatusTransitions();
        $currentStatus = $order->status;

        if (!isset($validTransitions[$currentStatus]) ||
            !in_array($newStatus, $validTransitions[$currentStatus])) {
            Log::warning('Invalid status transition attempted', [
                'order_id' => $order->id,
                'current_status' => $currentStatus,
                'new_status' => $newStatus,
            ]);
            return false;
        }

        // Handle specific status changes
        return match ($newStatus) {
            Order::STATUS_PREPARING => $this->markPreparing($order),
            Order::STATUS_READY => $this->markReady($order),
            Order::STATUS_ON_DELIVERY => $this->markPickedUp($order),
            Order::STATUS_DELIVERED => $this->markDelivered($order),
            Order::STATUS_CANCELLED => $this->markCancelled($order),
            default => $order->update(['status' => $newStatus]),
        };
    }

    /**
     * Mark order as preparing
     */
    public function markPreparing(Order $order): bool
    {
        $oldStatus = $order->status;

        $order->update([
            'status' => Order::STATUS_PREPARING,
            'accepted_at' => now(),
        ]);

        event(new OrderStatusUpdated($order, $oldStatus));

        $this->notificationService->sendStatusNotification($order, 'preparing');

        return true;
    }

    /**
     * Mark order as ready
     */
    public function markReady(Order $order): bool
    {
        $oldStatus = $order->status;

        $order->update([
            'status' => Order::STATUS_READY,
            'prepared_at' => now(),
        ]);

        event(new OrderStatusUpdated($order, $oldStatus));

        $this->notificationService->sendStatusNotification($order, 'ready');

        return true;
    }

    /**
     * Mark order as cancelled
     */
    public function markCancelled(Order $order, ?string $reason = null): bool
    {
        $oldStatus = $order->status;

        // Release courier if assigned
        if ($order->courier) {
            $order->courier->decrementActiveOrders();
        }

        $order->update([
            'status' => Order::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
            'courier_id' => null,
            'pool_entered_at' => null,
        ]);

        event(new OrderStatusUpdated($order, $oldStatus));

        $this->notificationService->sendStatusNotification($order, 'cancelled');

        return true;
    }

    /**
     * Get valid status transitions
     */
    protected function getValidStatusTransitions(): array
    {
        return [
            Order::STATUS_PENDING => [
                Order::STATUS_PREPARING,
                Order::STATUS_CANCELLED,
            ],
            Order::STATUS_PREPARING => [
                Order::STATUS_READY,
                Order::STATUS_CANCELLED,
            ],
            Order::STATUS_READY => [
                Order::STATUS_ON_DELIVERY,
                Order::STATUS_CANCELLED,
            ],
            Order::STATUS_ON_DELIVERY => [
                Order::STATUS_DELIVERED,
                Order::STATUS_CANCELLED,
                Order::STATUS_RETURNED,
            ],
            Order::STATUS_DELIVERED => [],
            Order::STATUS_CANCELLED => [],
            Order::STATUS_RETURNED => [],
        ];
    }

    /**
     * Get tracking steps for an order
     */
    public function getTrackingSteps(Order $order): array
    {
        return [
            [
                'key' => 'created',
                'label' => 'Sipariş Alındı',
                'icon' => 'clipboard-check',
                'completed' => true,
                'time' => $order->created_at,
            ],
            [
                'key' => 'preparing',
                'label' => 'Hazırlanıyor',
                'icon' => 'fire',
                'completed' => in_array($order->status, ['preparing', 'ready', 'on_delivery', 'delivered']),
                'time' => $order->accepted_at,
            ],
            [
                'key' => 'ready',
                'label' => 'Hazır',
                'icon' => 'check-circle',
                'completed' => in_array($order->status, ['ready', 'on_delivery', 'delivered']),
                'time' => $order->prepared_at,
            ],
            [
                'key' => 'picked_up',
                'label' => 'Kurye Aldı',
                'icon' => 'truck',
                'completed' => in_array($order->status, ['on_delivery', 'delivered']),
                'time' => $order->picked_up_at,
            ],
            [
                'key' => 'on_way',
                'label' => 'Yolda',
                'icon' => 'navigation',
                'completed' => $order->on_way_at !== null || $order->status === 'delivered',
                'time' => $order->on_way_at,
            ],
            [
                'key' => 'delivered',
                'label' => 'Teslim Edildi',
                'icon' => 'home',
                'completed' => $order->status === 'delivered',
                'time' => $order->delivered_at,
            ],
        ];
    }

    /**
     * Get current tracking step
     */
    public function getCurrentStep(Order $order): string
    {
        return match ($order->status) {
            'pending' => 'created',
            'preparing' => 'preparing',
            'ready' => 'ready',
            'on_delivery' => $order->on_way_at ? 'on_way' : 'picked_up',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            default => 'created',
        };
    }

    /**
     * Get estimated minutes remaining for delivery
     */
    public function getEstimatedMinutesRemaining(Order $order): ?int
    {
        if ($order->status === 'delivered' || $order->status === 'cancelled') {
            return 0;
        }

        if ($order->estimated_delivery_at) {
            $remaining = now()->diffInMinutes($order->estimated_delivery_at, false);
            return max(0, $remaining);
        }

        // Fallback estimation
        return match ($order->status) {
            'pending' => 45,
            'preparing' => 35,
            'ready' => 25,
            'on_delivery' => $order->estimated_minutes ?? 15,
            default => null,
        };
    }
}
