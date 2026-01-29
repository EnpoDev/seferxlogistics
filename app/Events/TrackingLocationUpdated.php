<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TrackingLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;
    public float $courierLat;
    public float $courierLng;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, float $courierLat, float $courierLng)
    {
        $this->order = $order;
        $this->courierLat = $courierLat;
        $this->courierLng = $courierLng;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('tracking.' . $this->order->tracking_token),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'location.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        // Calculate progress
        $trackingService = new \App\Services\TrackingService();
        $progress = $trackingService->getDeliveryProgress($this->order);

        return [
            'order_id' => $this->order->id,
            'status' => $this->order->status,
            'status_label' => $this->order->getStatusLabel(),
            'courier' => [
                'lat' => $this->courierLat,
                'lng' => $this->courierLng,
                'name' => $this->order->courier?->name,
            ],
            'progress' => $progress,
            'estimated_minutes' => $this->order->getEstimatedMinutesRemaining(),
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
