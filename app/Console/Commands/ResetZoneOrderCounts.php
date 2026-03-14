<?php

namespace App\Console\Commands;

use App\Models\Zone;
use Illuminate\Console\Command;

class ResetZoneOrderCounts extends Command
{
    protected $signature = 'zones:reset-daily-counts';

    protected $description = 'Reset daily order counts for all zones and re-enable them';

    public function handle(): int
    {
        $count = Zone::where('current_order_count', '>', 0)
            ->orWhere('is_active', false)
            ->update([
                'current_order_count' => 0,
                'is_active' => true,
            ]);

        $this->info("Reset order counts for {$count} zones.");

        return self::SUCCESS;
    }
}
