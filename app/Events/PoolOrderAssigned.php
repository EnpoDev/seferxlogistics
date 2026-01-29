<?php

namespace App\Events;

use App\Models\Courier;
use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PoolOrderAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;
    public Courier $courier;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, Courier $courier)
    {
        $this->order = $order;
        $this->courier = $courier;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('pool'),
            new Channel('couriers'),
            new Channel('courier.' . $this->courier->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'pool.order.assigned';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order' => [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'customer_name' => $this->order->customer_name,
                'customer_address' => $this->order->customer_address,
                'customer_phone' => $this->order->customer_phone,
                'lat' => (float) $this->order->lat,
                'lng' => (float) $this->order->lng,
                'total' => $this->order->total,
            ],
            'courier' => [
                'id' => $this->courier->id,
                'name' => $this->courier->name,
            ],
            'assigned_at' => now()->toIso8601String(),
        ];
    }
}
