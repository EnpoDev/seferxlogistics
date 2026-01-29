<?php

namespace App\Jobs;

use App\Models\Courier;
use App\Models\Order;
use App\Services\FcmPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProcessBatchNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;
    public int $timeout = 300;

    public function __construct(
        public string $notificationType,
        public array $recipientIds,
        public array $data
    ) {}

    public function handle(FcmPushService $fcmService): void
    {
        try {
            $recipients = $this->getRecipients();

            if ($recipients->isEmpty()) {
                Log::info('No recipients for batch notification', [
                    'type' => $this->notificationType,
                ]);
                return;
            }

            $deviceTokens = $recipients
                ->filter(fn($r) => !empty($r->device_token))
                ->pluck('device_token')
                ->toArray();

            if (empty($deviceTokens)) {
                Log::info('No device tokens for batch notification', [
                    'type' => $this->notificationType,
                    'recipient_count' => $recipients->count(),
                ]);
                return;
            }

            $notification = $this->buildNotification();

            $result = $fcmService->sendToMultipleDevices(
                $deviceTokens,
                $notification['title'],
                $notification['body'],
                $notification['data'] ?? []
            );

            Log::info('Batch notification sent', [
                'type' => $this->notificationType,
                'recipient_count' => count($deviceTokens),
                'success_count' => $result['success'] ?? 0,
                'failure_count' => $result['failure'] ?? 0,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send batch notification', [
                'type' => $this->notificationType,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function getRecipients(): Collection
    {
        return match ($this->notificationType) {
            'new_pool_order' => Courier::whereIn('id', $this->recipientIds)
                ->where('status', Courier::STATUS_AVAILABLE)
                ->where('notification_enabled', true)
                ->get(),
            'system_announcement' => Courier::whereIn('id', $this->recipientIds)->get(),
            default => collect(),
        };
    }

    protected function buildNotification(): array
    {
        return match ($this->notificationType) {
            'new_pool_order' => [
                'title' => 'Yeni Havuz Siparişi',
                'body' => $this->data['message'] ?? 'Havuzda yeni sipariş var!',
                'data' => [
                    'type' => 'pool_order',
                    'order_id' => $this->data['order_id'] ?? null,
                ],
            ],
            'system_announcement' => [
                'title' => $this->data['title'] ?? 'Sistem Bildirimi',
                'body' => $this->data['message'] ?? '',
                'data' => [
                    'type' => 'announcement',
                ],
            ],
            default => [
                'title' => 'Bildirim',
                'body' => $this->data['message'] ?? '',
                'data' => [],
            ],
        };
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Batch notification job failed permanently', [
            'type' => $this->notificationType,
            'recipient_count' => count($this->recipientIds),
            'error' => $exception->getMessage(),
        ]);
    }
}
