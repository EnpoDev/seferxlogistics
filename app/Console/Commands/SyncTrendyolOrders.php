<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Services\Integrations\TrendyolService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncTrendyolOrders extends Command
{
    protected $signature = 'trendyol:sync {--branch_id= : Sync orders for specific branch only}';
    protected $description = 'Sync orders from Trendyol platform automatically';

    public function handle(): int
    {
        $branchId = $this->option('branch_id');

        $query = Integration::where('platform', 'trendyol')
            ->where('is_connected', true);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $integrations = $query->get();

        if ($integrations->isEmpty()) {
            $this->info('No active Trendyol integrations found.');
            return self::SUCCESS;
        }

        $totalSynced = 0;

        foreach ($integrations as $integration) {
            try {
                $service = new TrendyolService();
                $service->setBranchId($integration->branch_id);
                $orders = $service->fetchOrders();
                $count = count($orders);
                $totalSynced += $count;

                $this->info("Branch #{$integration->branch_id}: {$count} order(s) synced.");

                Log::info('Trendyol auto-sync completed', [
                    'branch_id' => $integration->branch_id,
                    'orders_synced' => $count,
                ]);
            } catch (\Exception $e) {
                $this->error("Branch #{$integration->branch_id} sync failed: {$e->getMessage()}");

                Log::error('Trendyol auto-sync failed', [
                    'branch_id' => $integration->branch_id,
                    'error' => $e->getMessage(),
                ]);

                // Update integration status to error
                $integration->update([
                    'status' => 'error',
                    'error_message' => 'Otomatik senkronizasyon başarısız: ' . $e->getMessage(),
                ]);
            }
        }

        $this->info("Total synced: {$totalSynced} order(s).");
        return self::SUCCESS;
    }
}
