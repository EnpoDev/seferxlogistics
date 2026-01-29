<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use App\Services\ExternalWebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendExternalWebhook implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'webhooks';

    public function __construct(
        protected ExternalWebhookService $webhookService
    ) {}

    public function handle(OrderStatusUpdated $event): void
    {
        $order = $event->order;
        $oldStatus = $event->oldStatus;

        // Only send webhooks for external orders
        if (!$order->restaurant_connection_id) {
            return;
        }

        // Send status update webhook
        $this->webhookService->sendOrderStatusUpdate($order, $oldStatus);

        // Send specific webhooks based on new status
        match ($order->status) {
            'delivered' => $this->webhookService->sendOrderDelivered($order),
            'cancelled' => $this->webhookService->sendOrderCancelled($order),
            default => null,
        };

        // Check if courier was just assigned (status changed to on_delivery indicates courier assignment)
        if ($order->courier_id && $order->status === 'on_delivery' && $oldStatus !== 'on_delivery') {
            $this->webhookService->sendCourierAssigned($order);
        }
    }

    public function failed(OrderStatusUpdated $event, \Throwable $exception): void
    {
        \Log::error('Failed to send external webhook', [
            'order_id' => $event->order->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
