<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class GenerateTrackingTokens extends Command
{
    protected $signature = 'orders:generate-tracking-tokens';

    protected $description = 'Mevcut siparişler için tracking token oluştur';

    public function handle()
    {
        $orders = Order::whereNull('tracking_token')
            ->orWhere('tracking_token', '')
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Tüm siparişlerin tracking token\'ı mevcut.');
            return;
        }

        $this->info("Tracking token oluşturulacak sipariş sayısı: {$orders->count()}");

        $bar = $this->output->createProgressBar($orders->count());
        $bar->start();

        foreach ($orders as $order) {
            $order->update([
                'tracking_token' => Order::generateTrackingToken(),
            ]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Tüm siparişler için tracking token oluşturuldu!');
    }
}
