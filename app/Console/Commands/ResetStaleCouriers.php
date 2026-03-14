<?php

namespace App\Console\Commands;

use App\Models\Courier;
use Illuminate\Console\Command;

class ResetStaleCouriers extends Command
{
    protected $signature = 'couriers:reset-stale';

    protected $description = 'Reset busy couriers with no active orders to available status';

    public function handle(): int
    {
        $count = Courier::where('status', Courier::STATUS_BUSY)
            ->where('active_orders_count', '<', 1)
            ->where('updated_at', '<', now()->subMinutes(15))
            ->update(['status' => Courier::STATUS_AVAILABLE]);

        if ($count > 0) {
            $this->info("Reset {$count} stale busy couriers to available.");
        }

        return self::SUCCESS;
    }
}
