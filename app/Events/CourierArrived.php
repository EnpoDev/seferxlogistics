<?php

namespace App\Events;

use App\Models\Order;
use App\Models\Courier;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourierArrived implements ShouldBroadcast
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
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('order.' . $this->order->id),
            new PrivateChannel('branch.' . $this->order->branch_id),
            new PrivateChannel('tracking.' . $this->order->tracking_token),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'courier.arrived';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'courier' => [
                'id' => $this->courier->id,
                'name' => $this->courier->name,
                'phone' => $this->courier->phone,
            ],
            'arrived_at' => now()->format('H:i'),
            'message' => 'Kuryeniz teslimat adresinize ulaştı!',
        ];
    }
}
