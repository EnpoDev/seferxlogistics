<?php

namespace App\Jobs;

use App\Models\RestaurantConnection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        $signature = $this->generateSignature($this->payload, $this->webhookSecret);

        try {
            $response = Http::timeout(15)
                ->retry(2, 1000) // Quick retry within same attempt
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $this->event,
                    'X-Connection-Id' => (string) $this->connectionId,
                ])
                ->post($this->webhookUrl, $this->payload);

            if ($response->successful()) {
                Log::info('Webhook delivered successfully', [
                    'connection_id' => $this->connectionId,
                    'event' => $this->event,
                    'status' => $response->status(),
                    'attempt' => $this->attempts(),
                ]);
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

        // Optionally: Mark connection as having delivery issues
        $connection = RestaurantConnection::find($this->connectionId);
        if ($connection) {
            $settings = $connection->settings ?? [];
            $settings['last_webhook_failure'] = now()->toIso8601String();
            $settings['webhook_failure_count'] = ($settings['webhook_failure_count'] ?? 0) + 1;
            $connection->update(['settings' => $settings]);
        }
    }

    protected function generateSignature(array $payload, ?string $secret): string
    {
        if (!$secret) {
            return '';
        }

        return hash_hmac('sha256', json_encode($payload), $secret);
    }
}
