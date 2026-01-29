<?php

namespace App\Jobs;

use App\Services\PoolService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPoolTimeouts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(PoolService $poolService): void
    {
        try {
            $assigned = $poolService->processTimeoutOrders();

            if ($assigned > 0) {
                Log::info('ProcessPoolTimeouts job completed', [
                    'assigned_count' => $assigned,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('ProcessPoolTimeouts job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
