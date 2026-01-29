<?php

namespace App\Listeners;

use App\Events\OrderStatusUpdated;
use App\Jobs\SendCustomerNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SendCustomerNotificationListener implements ShouldQueue
{
    public function handle(OrderStatusUpdated $event): void
    {
        $order = $event->order;

        // Determine notification type based on status
        $notificationType = match ($order->status) {
            'preparing' => 'preparing',
            'ready' => 'ready',
            'on_delivery' => $order->on_way_at ? 'on_way' : 'picked_up',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            default => null,
        };

        if ($notificationType) {
            // Dispatch as a job for async processing
            SendCustomerNotificationJob::dispatch($order, $notificationType);

            Log::info('Customer notification dispatched', [
                'order_id' => $order->id,
                'status' => $order->status,
                'type' => $notificationType,
            ]);
        }
    }
}
