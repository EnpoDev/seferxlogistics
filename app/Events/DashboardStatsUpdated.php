<?php

namespace App\Events;

use App\Models\Courier;
use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardStatsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ?int $branchId;

    /**
     * Create a new event instance.
     */
    public function __construct(?int $branchId = null)
    {
        $this->branchId = $branchId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('dashboard'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'stats.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $orderQuery = Order::query();
        $courierQuery = Courier::query();

        // Apply branch filter if provided
        if ($this->branchId) {
            $orderQuery->where('branch_id', $this->branchId);
        }

        // Get order counts by status
        $orders = [
            'pending' => (clone $orderQuery)->where('status', 'pending')->count(),
            'preparing' => (clone $orderQuery)->where('status', 'preparing')->count(),
            'ready' => (clone $orderQuery)->where('status', 'ready')->count(),
            'on_delivery' => (clone $orderQuery)->where('status', 'on_delivery')->count(),
            'delivered' => (clone $orderQuery)->whereDate('created_at', today())->where('status', 'delivered')->count(),
            'cancelled' => (clone $orderQuery)->whereDate('created_at', today())->where('status', 'cancelled')->count(),
        ];

        // Get courier counts by status
        $couriers = [
            'available' => $courierQuery->where('status', Courier::STATUS_AVAILABLE)->count(),
            'busy' => Courier::where('status', Courier::STATUS_BUSY)->count(),
            'offline' => Courier::where('status', Courier::STATUS_OFFLINE)->count(),
        ];

        // Get pool order count
        $poolQuery = Order::whereNotNull('pool_entered_at');
        if ($this->branchId) {
            $poolQuery->where('branch_id', $this->branchId);
        }
        $poolCount = $poolQuery->count();

        return [
            'orders' => $orders,
            'couriers' => $couriers,
            'pool_count' => $poolCount,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
