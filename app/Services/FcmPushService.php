<?php

namespace App\Services;

use App\Models\Courier;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmPushService
{
    protected ?string $serverKey;
    protected string $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->serverKey = config('services.firebase.server_key');
    }

    /**
     * Check if FCM is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->serverKey);
    }

    /**
     * Send push notification to a single device
     */
    public function sendToDevice(string $deviceToken, array $notification, array $data = []): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('FCM server key not configured');
            return false;
        }

        $payload = [
            'to' => $deviceToken,
            'notification' => [
                'title' => $notification['title'] ?? 'Bildirim',
                'body' => $notification['body'] ?? '',
                'sound' => $notification['sound'] ?? 'default',
                'badge' => $notification['badge'] ?? 1,
                'click_action' => $data['click_action'] ?? 'FLUTTER_NOTIFICATION_CLICK',
            ],
            'data' => array_merge($data, [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ]),
            'priority' => 'high',
            'content_available' => true,
        ];

        return $this->send($payload);
    }

    /**
     * Send push notification to multiple devices
     */
    public function sendToMultipleDevices(array $deviceTokens, array $notification, array $data = []): array
    {
        if (!$this->isConfigured()) {
            Log::warning('FCM server key not configured');
            return ['success' => false, 'error' => 'FCM not configured'];
        }

        if (empty($deviceTokens)) {
            return ['success' => true, 'sent' => 0];
        }

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        // FCM multicast limit: 500 tokens per request
        $chunks = array_chunk($deviceTokens, 500);

        foreach ($chunks as $chunk) {
            $payload = [
                'registration_ids' => $chunk,
                'notification' => [
                    'title' => $notification['title'] ?? 'Bildirim',
                    'body' => $notification['body'] ?? '',
                    'sound' => $notification['sound'] ?? 'pool_order',
                    'badge' => $notification['badge'] ?? 1,
                ],
                'data' => array_merge($data, [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]),
                'priority' => 'high',
                'content_available' => true,
            ];

            $response = $this->sendRaw($payload);
            $results[] = $response;

            if ($response['success']) {
                $successCount += $response['data']['success'] ?? 0;
                $failureCount += $response['data']['failure'] ?? 0;
            }
        }

        return [
            'success' => true,
            'sent' => $successCount,
            'failed' => $failureCount,
            'details' => $results,
        ];
    }

    /**
     * Notify all available couriers about a new pool order
     */
    public function notifyPoolOrder(Order $order): array
    {
        // Get available couriers who are on shift and have device tokens
        $couriers = Courier::where('notification_enabled', true)
            ->whereNotNull('device_token')
            ->where('device_token', '!=', '')
            ->where(function ($query) {
                $query->where('status', Courier::STATUS_AVAILABLE)
                    ->orWhere(function ($q) {
                        $q->where('status', Courier::STATUS_BUSY)
                          ->where('active_orders_count', '<', Courier::MAX_ACTIVE_ORDERS);
                    });
            })
            ->get()
            ->filter(fn($c) => $c->isOnShift());

        if ($couriers->isEmpty()) {
            Log::info('No couriers available for pool notification', ['order_id' => $order->id]);
            return ['success' => true, 'sent' => 0, 'message' => 'No available couriers'];
        }

        $deviceTokens = $couriers->pluck('device_token')->filter()->unique()->toArray();

        $notification = [
            'title' => 'Yeni Sipariş!',
            'body' => sprintf(
                '#%s - %s TL - %s',
                $order->order_number,
                number_format($order->total, 2, ',', '.'),
                \Illuminate\Support\Str::limit($order->customer_address, 40)
            ),
            'sound' => 'pool_order',
        ];

        $data = [
            'type' => 'pool_order',
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'total' => (string) $order->total,
            'customer_address' => $order->customer_address,
            'action' => 'open_pool',
        ];

        $result = $this->sendToMultipleDevices($deviceTokens, $notification, $data);

        Log::info('Pool order FCM notification sent', [
            'order_id' => $order->id,
            'courier_count' => count($deviceTokens),
            'result' => $result,
        ]);

        return $result;
    }

    /**
     * Notify a specific courier about an assigned order
     */
    public function notifyCourierAssignment(Order $order, Courier $courier): bool
    {
        if (!$courier->device_token) {
            Log::info('Courier has no device token', ['courier_id' => $courier->id]);
            return false;
        }

        $notification = [
            'title' => 'Sipariş Atandı!',
            'body' => sprintf(
                '#%s - %s - %s TL',
                $order->order_number,
                \Illuminate\Support\Str::limit($order->customer_address, 30),
                number_format($order->total, 2, ',', '.')
            ),
        ];

        $data = [
            'type' => 'order_assigned',
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'action' => 'open_order',
        ];

        return $this->sendToDevice($courier->device_token, $notification, $data);
    }

    /**
     * Send raw FCM request
     */
    protected function send(array $payload): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                return ($data['success'] ?? 0) > 0;
            }

            Log::error('FCM send failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('FCM exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send raw FCM request and return full response
     */
    protected function sendRaw(array $payload): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $response->json(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
