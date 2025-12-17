<?php

namespace App\Events;

use App\Models\Courier;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourierLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Courier $courier;

    /**
     * Create a new event instance.
     */
    public function __construct(Courier $courier)
    {
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
            new Channel('couriers'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'courier.location.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->courier->id,
            'name' => $this->courier->name,
            'lat' => $this->courier->lat,
            'lng' => $this->courier->lng,
            'status' => $this->courier->status,
            'status_label' => $this->courier->getStatusLabel(),
            'active_orders_count' => $this->courier->active_orders_count,
            'updated_at' => now()->toISOString(),
        ];
    }
}

