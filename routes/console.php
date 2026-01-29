<?php

use App\Jobs\ProcessPoolTimeouts;
use App\Jobs\SyncIntegrationOrders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pool timeout siparişlerini her dakika kontrol et
Schedule::job(new ProcessPoolTimeouts)->everyMinute();

// Entegrasyon siparişlerini her 1 dakikada senkronize et (Trendyol Go, Getir, Yemeksepeti)
Schedule::job(new SyncIntegrationOrders)->everyMinute();

// Günlük settlement hesaplama (her gün gece yarısı 00:30'da dünün siparişleri için)
Schedule::command('settlements:calculate')->dailyAt('00:30')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/settlements.log'));

// Manuel senkronizasyon komutu
Artisan::command('integrations:sync {platform?}', function (?string $platform = null) {
    $this->info('Starting integration sync...');
    SyncIntegrationOrders::dispatch($platform);
    $this->info('Sync job dispatched.');
})->purpose('Sync orders from integrated platforms');

// Queue worker - shared hosting için (her dakika bekleyen job'ları işle)
Schedule::command('queue:work --stop-when-empty --max-time=50')->everyMinute()->withoutOverlapping();
