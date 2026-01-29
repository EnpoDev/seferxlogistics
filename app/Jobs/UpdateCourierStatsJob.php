<?php

namespace App\Jobs;

use App\Models\Courier;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateCourierStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;
    public int $timeout = 120;

    public function __construct(
        public Courier $courier
    ) {}

    public function handle(): void
    {
        try {
            DB::transaction(function () {
                // Calculate total deliveries
                $totalDeliveries = Order::where('courier_id', $this->courier->id)
                    ->where('status', Order::STATUS_DELIVERED)
                    ->count();

                // Calculate average delivery time
                $avgDeliveryTime = Order::where('courier_id', $this->courier->id)
                    ->where('status', Order::STATUS_DELIVERED)
                    ->whereNotNull('delivered_at')
                    ->whereNotNull('created_at')
                    ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, delivered_at)) as avg_time')
                    ->value('avg_time');

                // Calculate active orders count
                $activeOrdersCount = Order::where('courier_id', $this->courier->id)
                    ->whereIn('status', [
                        Order::STATUS_READY,
                        Order::STATUS_ON_DELIVERY,
                    ])
                    ->count();

                // Update courier
                $this->courier->update([
                    'total_deliveries' => $totalDeliveries,
                    'average_delivery_time' => $avgDeliveryTime ?? 0,
                    'active_orders_count' => $activeOrdersCount,
                ]);

                Log::info('Courier stats updated', [
                    'courier_id' => $this->courier->id,
                    'total_deliveries' => $totalDeliveries,
                    'avg_delivery_time' => $avgDeliveryTime,
                    'active_orders' => $activeOrdersCount,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Failed to update courier stats', [
                'courier_id' => $this->courier->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Courier stats update job failed permanently', [
            'courier_id' => $this->courier->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
