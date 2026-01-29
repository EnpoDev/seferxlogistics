<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\CustomerNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCustomerNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;
    public int $timeout = 60;

    public function __construct(
        public Order $order,
        public string $notificationType
    ) {}

    public function handle(CustomerNotificationService $notificationService): void
    {
        try {
            match ($this->notificationType) {
                'courier_assigned' => $notificationService->sendCourierAssignedNotification($this->order),
                'status_update' => $notificationService->sendStatusNotification($this->order, $this->order->status),
                'picked_up' => $notificationService->sendStatusNotification($this->order, 'picked_up'),
                'on_way' => $notificationService->sendStatusNotification($this->order, 'on_way'),
                'delivered' => $notificationService->sendStatusNotification($this->order, 'delivered'),
                'cancelled' => $notificationService->sendStatusNotification($this->order, 'cancelled'),
                default => Log::warning('Unknown notification type', [
                    'type' => $this->notificationType,
                    'order_id' => $this->order->id,
                ]),
            };

            Log::info('Customer notification sent', [
                'order_id' => $this->order->id,
                'type' => $this->notificationType,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send customer notification', [
                'order_id' => $this->order->id,
                'type' => $this->notificationType,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Customer notification job failed permanently', [
            'order_id' => $this->order->id,
            'type' => $this->notificationType,
            'error' => $exception->getMessage(),
        ]);
    }
}
