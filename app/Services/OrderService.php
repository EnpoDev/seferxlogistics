<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Courier;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /**
     * Generate a unique order number
     */
    public function generateOrderNumber(): string
    {
        $lastOrder = Order::latest('id')->first();
        $nextId = $lastOrder ? $lastOrder->id + 1 : 1;
        return 'ORD-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a unique tracking token
     */
    public function generateTrackingToken(): string
    {
        do {
            $token = strtoupper(bin2hex(random_bytes(8)));
        } while (Order::where('tracking_token', $token)->exists());

        return $token;
    }

    /**
     * Create a new order with all necessary initializations
     */
    public function createOrder(array $data): Order
    {
        if (empty($data['order_number'])) {
            $data['order_number'] = $this->generateOrderNumber();
        }

        if (empty($data['tracking_token'])) {
            $data['tracking_token'] = $this->generateTrackingToken();
        }

        $data['status'] = $data['status'] ?? Order::STATUS_PENDING;

        return Order::create($data);
    }

    /**
     * Find order by tracking token
     */
    public function findByTrackingToken(string $token): ?Order
    {
        return Order::where('tracking_token', $token)->first();
    }

    /**
     * Get tracking URL for an order
     */
    public function getTrackingUrl(Order $order): string
    {
        return route('tracking.show', $order->tracking_token);
    }

    /**
     * Calculate delivery time in minutes
     */
    public function getDeliveryTimeInMinutes(Order $order): ?int
    {
        if (!$order->delivered_at || !$order->created_at) {
            return null;
        }

        return $order->created_at->diffInMinutes($order->delivered_at);
    }

    /**
     * Update customer statistics after order completion
     */
    public function updateCustomerStats(Order $order): void
    {
        if ($order->customer) {
            $order->customer->updateOrderStats();
        }
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled(Order $order): bool
    {
        return in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_PREPARING]);
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(Order $order, ?string $reason = null): bool
    {
        if (!$this->canBeCancelled($order)) {
            return false;
        }

        return $order->update([
            'status' => Order::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ]);
    }

    /**
     * Get orders for a specific customer
     */
    public function getCustomerOrders(Customer $customer, int $limit = 10)
    {
        return Order::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get active orders count for today
     */
    public function getTodayActiveOrdersCount(): int
    {
        return Order::whereDate('created_at', today())
            ->whereNotIn('status', [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED])
            ->count();
    }

    /**
     * Get orders statistics for a date range
     */
    public function getOrdersStatistics(\DateTime $startDate, \DateTime $endDate): array
    {
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])->get();

        return [
            'total' => $orders->count(),
            'delivered' => $orders->where('status', Order::STATUS_DELIVERED)->count(),
            'cancelled' => $orders->where('status', Order::STATUS_CANCELLED)->count(),
            'total_revenue' => $orders->where('status', Order::STATUS_DELIVERED)->sum('total'),
            'average_delivery_time' => $this->calculateAverageDeliveryTime($orders),
        ];
    }

    /**
     * Calculate average delivery time from a collection of orders
     */
    protected function calculateAverageDeliveryTime($orders): ?float
    {
        $deliveredOrders = $orders->filter(function ($order) {
            return $order->status === Order::STATUS_DELIVERED
                && $order->delivered_at
                && $order->created_at;
        });

        if ($deliveredOrders->isEmpty()) {
            return null;
        }

        $totalMinutes = $deliveredOrders->sum(function ($order) {
            return $order->created_at->diffInMinutes($order->delivered_at);
        });

        return round($totalMinutes / $deliveredOrders->count(), 1);
    }
}
