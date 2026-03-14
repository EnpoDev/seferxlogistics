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
        $platforms = ['yemeksepeti', 'getir', 'trendyol'];

        // Belirli platform veya tum platformlar
        if ($this->platform && in_array($this->platform, $platforms)) {
            $platforms = [$this->platform];
        }

        // Her platform icin tum baglanti olan entegrasyonlari sync et
        foreach ($platforms as $platform) {
            $integrations = Integration::where('platform', $platform)
                ->where('is_connected', true)
                ->get();

            foreach ($integrations as $integration) {
                $this->syncIntegration($platform, $integration);
            }
        }
    }

    private function syncIntegration(string $platform, Integration $integration): void
    {
        $service = match ($platform) {
            'yemeksepeti' => new YemeksepetiService(),
            'getir' => new GetirService(),
            'trendyol' => new TrendyolService(),
            default => null,
        };

        if (!$service) {
            return;
        }

        $service->setIntegration($integration);

        try {
            Log::info("[Integration Sync] Starting sync for {$platform} (branch: {$integration->branch_id})");

            $orders = $service->fetchOrders();

            Log::info("[Integration Sync] Synced {$platform} (branch: {$integration->branch_id}): " . count($orders) . " orders");

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
            Log::error("[Integration Sync] Error syncing {$platform} (branch: {$integration->branch_id}): " . $e->getMessage());

            $integration->markAsError($e->getMessage());
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("[Integration Sync] Job failed: " . $exception->getMessage());
    }
}
