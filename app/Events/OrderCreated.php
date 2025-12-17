<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order->load(['customer', 'courier', 'items', 'restaurant']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('orders'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.created';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'customer_name' => $this->order->customer_name,
            'customer_phone' => $this->order->customer_phone,
            'customer_address' => $this->order->customer_address,
            'lat' => $this->order->lat,
            'lng' => $this->order->lng,
            'status' => $this->order->status,
            'status_label' => $this->order->getStatusLabel(),
            'total' => $this->order->total,
            'payment_method' => $this->order->payment_method,
            'courier' => $this->order->courier ? [
                'id' => $this->order->courier->id,
                'name' => $this->order->courier->name,
            ] : null,
            'restaurant' => $this->order->restaurant ? [
                'id' => $this->order->restaurant->id,
                'name' => $this->order->restaurant->name,
            ] : null,
            'items_count' => $this->order->items->count(),
            'created_at' => $this->order->created_at->toISOString(),
        ];
    }
}

