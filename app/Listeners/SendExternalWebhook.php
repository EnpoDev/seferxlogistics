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

    protected ?int $previousCourierId = null;

    public function __construct(
        protected ExternalWebhookService $webhookService
    ) {}

    public function handle(OrderStatusUpdated $event): void
    {
        $order = $event->order;
        $oldStatus = $event->oldStatus;
        $previousCourierId = $event->previousCourierId ?? null;

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

        // Check if courier was just assigned (wasn't assigned before, now it is)
        if ($order->courier_id && $previousCourierId !== $order->courier_id) {
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
