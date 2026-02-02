<?php

namespace App\Jobs;

use App\Models\RestaurantConnection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendWebhookJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 5;
    public $backoff = [30, 60, 300, 900, 3600]; // 30s, 1m, 5m, 15m, 1h

    public function __construct(
        public int $connectionId,
        public string $event,
        public array $payload,
        public string $webhookUrl,
        public ?string $webhookSecret
    ) {
        $this->onQueue('webhooks');
    }

    public function handle(): void
    {
        // Generate unique webhook ID for deduplication (consistent across retries)
        $webhookId = $this->job?->uuid() ?? Str::uuid()->toString();

        // Encode payload to JSON once - use same encoding as HTTP client for signature consistency
        $jsonBody = json_encode($this->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = $this->generateSignature($jsonBody, $this->webhookSecret);

        try {
            $response = Http::timeout(15)
                ->retry(2, 1000) // Quick retry within same attempt
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $this->event,
                    'X-Webhook-Id' => $webhookId,
                    'X-Connection-Id' => (string) $this->connectionId,
                ])
                ->withBody($jsonBody, 'application/json')
                ->post($this->webhookUrl);

            if ($response->successful()) {
                Log::info('Webhook delivered successfully', [
                    'connection_id' => $this->connectionId,
                    'event' => $this->event,
                    'status' => $response->status(),
                    'attempt' => $this->attempts(),
                ]);

                // Reset failure count on successful delivery
                $connection = RestaurantConnection::find($this->connectionId);
                if ($connection) {
                    $settings = $connection->settings ?? [];
                    if (($settings['webhook_failure_count'] ?? 0) > 0) {
                        $settings['webhook_failure_count'] = 0;
                        $settings['last_webhook_success'] = now()->toIso8601String();
                        $connection->update(['settings' => $settings]);
                    }
                }

                return;
            }

            // Log failure and let it retry
            Log::warning('Webhook delivery failed, will retry', [
                'connection_id' => $this->connectionId,
                'event' => $this->event,
                'status' => $response->status(),
                'response' => substr($response->body(), 0, 500),
                'attempt' => $this->attempts(),
            ]);

            // Throw exception to trigger retry
            throw new \Exception("Webhook failed with status: {$response->status()}");

        } catch (\Exception $e) {
            Log::error('Webhook delivery error', [
                'connection_id' => $this->connectionId,
                'event' => $this->event,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e; // Re-throw to trigger queue retry
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Webhook delivery permanently failed after all retries', [
            'connection_id' => $this->connectionId,
            'event' => $this->event,
            'webhook_url' => $this->webhookUrl,
            'error' => $exception->getMessage(),
            'payload' => $this->payload,
        ]);

        // Mark connection as having delivery issues
        $connection = RestaurantConnection::find($this->connectionId);
        if ($connection) {
            $settings = $connection->settings ?? [];
            $settings['last_webhook_failure'] = now()->toIso8601String();
            $failureCount = ($settings['webhook_failure_count'] ?? 0) + 1;
            $settings['webhook_failure_count'] = $failureCount;
            $connection->update(['settings' => $settings]);

            // Alert after 3 consecutive failures
            if ($failureCount >= 3) {
                Log::channel('slack')->critical('Webhook delivery failing repeatedly', [
                    'connection_id' => $this->connectionId,
                    'external_restaurant_id' => $connection->external_restaurant_id,
                    'external_restaurant_name' => $connection->external_restaurant_name,
                    'webhook_url' => $this->webhookUrl,
                    'failure_count' => $failureCount,
                    'last_error' => $exception->getMessage(),
                ]);

                // Dispatch event for additional alerting mechanisms
                event(new \App\Events\WebhookDeliveryFailed(
                    $connection,
                    $this->event,
                    $failureCount,
                    $exception->getMessage()
                ));
            }
        }
    }

    protected function generateSignature(string $jsonBody, ?string $secret): string
    {
        if (!$secret) {
            Log::warning('Webhook sent without signature - no secret configured', [
                'connection_id' => $this->connectionId,
            ]);
            return '';
        }

        return hash_hmac('sha256', $jsonBody, $secret);
    }
}
