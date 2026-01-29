<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Services\Integrations\YemeksepetiService;
use App\Services\Integrations\GetirService;
use App\Services\Integrations\TrendyolService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncIntegrationOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    private ?string $platform;

    public function __construct(?string $platform = null)
    {
        $this->platform = $platform;
    }

    public function handle(): void
    {
        $services = [
            'yemeksepeti' => new YemeksepetiService(),
            'getir' => new GetirService(),
            'trendyol' => new TrendyolService(),
        ];

        // Belirli platform veya tum platformlar
        if ($this->platform && isset($services[$this->platform])) {
            $this->syncPlatform($this->platform, $services[$this->platform]);
        } else {
            foreach ($services as $platform => $service) {
                $this->syncPlatform($platform, $service);
            }
        }
    }

    private function syncPlatform(string $platform, $service): void
    {
        $integration = $service->getIntegration();

        if (!$integration || !$integration->is_connected) {
            return;
        }

        try {
            Log::info("[Integration Sync] Starting sync for {$platform}");

            $orders = $service->fetchOrders();

            Log::info("[Integration Sync] Synced {$platform}: " . count($orders) . " orders");

            // Entegrasyon istatistiklerini guncelle
            $integration->update([
                'last_sync_at' => now(),
                'sync_count' => ($integration->sync_count ?? 0) + 1,
                'error_message' => null,
            ]);

            // Yeni siparis geldi bildirimi
            foreach ($orders as $order) {
                if ($order->wasRecentlyCreated) {
                    event(new \App\Events\OrderCreated($order));
                }
            }
        } catch (\Exception $e) {
            Log::error("[Integration Sync] Error syncing {$platform}: " . $e->getMessage());

            if ($integration) {
                $integration->markAsError($e->getMessage());
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("[Integration Sync] Job failed: " . $exception->getMessage());
    }
}
