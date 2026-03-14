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

// Abonelik durumu kontrolu (her saat basta)
// Suresi dolan abonelikleri expired yapar
Schedule::command('app:check-subscription-status')->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/subscriptions.log'));

// Zone günlük sipariş sayaçlarını sıfırla (her gün gece yarısı)
Schedule::command('zones:reset-daily-counts')->dailyAt('00:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/zones.log'));

// Meşgul ama aktif siparişi olmayan kuryeleri 15 dakika sonra serbest bırak
Schedule::command('couriers:reset-stale')->everyFifteenMinutes()
    ->withoutOverlapping();

// Bağlantı kurma aşamasında kalan entegrasyonları 30 dakika sonra hatalı olarak işaretle
Schedule::command('integrations:cleanup-stale')->everyThirtyMinutes()
    ->withoutOverlapping();

// Manuel senkronizasyon komutu
Artisan::command('integrations:sync {platform?}', function (?string $platform = null) {
    $this->info('Starting integration sync...');
    SyncIntegrationOrders::dispatch($platform);
    $this->info('Sync job dispatched.');
})->purpose('Sync orders from integrated platforms');

// Trendyol siparişlerini her 5 dakikada bir senkronize et (standalone fallback)
Schedule::command('trendyol:sync')->everyFiveMinutes()->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/trendyol-sync.log'));

// Queue worker - shared hosting için (her dakika bekleyen job'ları işle)
Schedule::command('queue:work --stop-when-empty --max-time=50')->everyMinute()->withoutOverlapping();
