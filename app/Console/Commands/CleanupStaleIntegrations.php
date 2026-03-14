<?php

namespace App\Console\Commands;

use App\Models\Integration;
use Illuminate\Console\Command;

class CleanupStaleIntegrations extends Command
{
    protected $signature = 'integrations:cleanup-stale';

    protected $description = 'Mark integrations stuck in connecting state as failed';

    public function handle(): int
    {
        $count = Integration::where('status', 'connecting')
            ->where('updated_at', '<', now()->subMinutes(30))
            ->update([
                'status' => 'error',
                'error_message' => 'Bağlantı zaman aşımına uğradı.',
                'is_connected' => false,
            ]);

        if ($count > 0) {
            $this->info("Marked {$count} stale integrations as failed.");
        }

        return self::SUCCESS;
    }
}
