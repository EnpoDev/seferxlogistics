<?php

namespace App\Services;

use App\Jobs\SendWebhookJob;
use App\Models\Order;
use App\Models\RestaurantConnection;
use Illuminate\Support\Facades\Log;

class ExternalWebhookService
{
    /**
     * Send order status update webhook
     */
    public function sendOrderStatusUpdate(Order $order, string $oldStatus): void
    {
        if (!$order->restaurant_connection_id) {
            return;
        }

        $connection = $order->restaurantConnection;
        if (!$connection || !$connection->webhook_url) {
            return;
        }

        $this->sendWebhook($connection, 'order.status_updated', [
            'external_order_id' => $order->external_order_id,
            'order_number' => $order->order_number,
            'old_status' => $oldStatus,
            'new_status' => $order->status,
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Send courier assigned webhook
     */
    public function sendCourierAssigned(Order $order): void
    {
        if (!$order->restaurant_connection_id) {
            return;
        }

        $connection = $order->restaurantConnection;
        if (!$connection || !$connection->webhook_url) {
            return;
        }

        $courier = $order->courier;

        $this->sendWebhook($connection, 'order.courier_assigned', [
            'external_order_id' => $order->external_order_id,
            'order_number' => $order->order_number,
            'courier' => $courier ? [
                'name' => $courier->name,
                'phone' => $courier->phone,
            ] : null,
            'estimated_delivery_at' => $order->estimated_delivery_at?->toIso8601String(),
            'assigned_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Send order delivered webhook
     */
    public function sendOrderDelivered(Order $order): void
    {
        if (!$order->restaurant_connection_id) {
            return;
        }

        $connection = $order->restaurantConnection;
        if (!$connection || !$connection->webhook_url) {
            return;
        }

        $this->sendWebhook($connection, 'order.delivered', [
            'external_order_id' => $order->external_order_id,
            'order_number' => $order->order_number,
            'delivered_at' => $order->delivered_at?->toIso8601String(),
            'delivery_duration_minutes' => $order->getDeliveryTimeInMinutes(),
            'pod' => $order->hasPod() ? [
                'photo_url' => $order->getPodPhotoUrl(),
                'timestamp' => $order->pod_timestamp?->toIso8601String(),
                'note' => $order->pod_note,
            ] : null,
        ]);
    }

    /**
     * Send order cancelled webhook
     */
    public function sendOrderCancelled(Order $order): void
    {
        if (!$order->restaurant_connection_id) {
            return;
        }

        $connection = $order->restaurantConnection;
        if (!$connection || !$connection->webhook_url) {
            return;
        }

        $this->sendWebhook($connection, 'order.cancelled', [
            'external_order_id' => $order->external_order_id,
            'order_number' => $order->order_number,
            'cancel_reason' => $order->cancel_reason,
            'cancelled_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Send courier location update webhook
     */
    public function sendCourierLocationUpdate(Order $order, float $lat, float $lng): void
    {
        if (!$order->restaurant_connection_id) {
            return;
        }

        $connection = $order->restaurantConnection;
        if (!$connection || !$connection->webhook_url) {
            return;
        }

        $settings = $connection->settings ?? [];

        // Check if location updates are enabled in settings
        if (!($settings['send_location_updates'] ?? true)) {
            return;
        }

        $this->sendWebhook($connection, 'courier.location_updated', [
            'external_order_id' => $order->external_order_id,
            'order_number' => $order->order_number,
            'courier_location' => [
                'lat' => $lat,
                'lng' => $lng,
            ],
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Send webhook to external platform via queue with retry logic
     */
    protected function sendWebhook(RestaurantConnection $connection, string $event, array $data): void
    {
        $payload = [
            'event' => $event,
            'connection_id' => $connection->id,
            'external_restaurant_id' => $connection->external_restaurant_id,
            'timestamp' => now()->toIso8601String(),
            'data' => $data,
        ];

        // Dispatch webhook job with retry logic
        SendWebhookJob::dispatch(
            $connection->id,
            $event,
            $payload,
            $connection->webhook_url,
            $connection->webhook_secret
        );

        Log::info('Webhook job dispatched', [
            'connection_id' => $connection->id,
            'event' => $event,
        ]);
    }
}
