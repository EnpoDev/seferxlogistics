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

class CourierStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Courier $courier;
    public string $oldStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(Courier $courier, string $oldStatus)
    {
        $this->courier = $courier;
        $this->oldStatus = $oldStatus;
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
        return 'courier.status.changed';
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
            'phone' => $this->courier->phone,
            'old_status' => $this->oldStatus,
            'new_status' => $this->courier->status,
            'status_label' => $this->courier->getStatusLabel(),
            'lat' => $this->courier->lat,
            'lng' => $this->courier->lng,
            'active_orders_count' => $this->courier->active_orders_count,
            'vehicle_plate' => $this->courier->vehicle_plate,
            'updated_at' => now()->toISOString(),
        ];
    }
}

