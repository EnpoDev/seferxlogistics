<?php

namespace App\Console\Commands;

use App\Services\SettlementService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CalculateDailySettlements extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'settlements:calculate
                            {--date= : Hesaplama yapılacak tarih (YYYY-MM-DD formatında, varsayılan: dün)}
                            {--branch= : Sadece belirli bir branch için hesapla}
                            {--dry-run : Hesaplama yap ama kaydetme}';

    /**
     * The console command description.
     */
    protected $description = 'Günlük gelir dağılımı hesaplamalarını yapar';

    public function __construct(
        private SettlementService $settlementService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Tarihi belirle
        $dateInput = $this->option('date');
        $date = $dateInput
            ? Carbon::parse($dateInput)
            : Carbon::yesterday();

        $branchId = $this->option('branch') ? (int) $this->option('branch') : null;
        $dryRun = $this->option('dry-run');

        $this->info('');
        $this->info('===========================================');
        $this->info('  Günlük Settlement Hesaplama');
        $this->info('===========================================');
        $this->info('');
        $this->info("Tarih: {$date->format('Y-m-d')} ({$date->locale('tr')->dayName})");

        if ($branchId) {
            $this->info("Branch ID: {$branchId}");
        }

        if ($dryRun) {
            $this->warn('DRY RUN - Değişiklikler kaydedilmeyecek');
        }

        $this->info('');

        // Hesaplamaları yap
        if ($dryRun) {
            $this->dryRunCalculation($date, $branchId);
        } else {
            $this->executeCalculation($date, $branchId);
        }

        return Command::SUCCESS;
    }

    /**
     * Gerçek hesaplama ve kaydetme
     */
    private function executeCalculation(Carbon $date, ?int $branchId): void
    {
        $settlements = $this->settlementService->calculateForDate($date, $branchId);

        if ($settlements->isEmpty()) {
            $this->warn('Bu tarih için hesaplanacak sipariş bulunamadı.');
            return;
        }

        $this->info("Oluşturulan Settlement Sayısı: {$settlements->count()}");
        $this->info('');

        // Detaylı çıktı
        $this->table(
            ['ID', 'Branch', 'Restoran', 'Sipariş', 'Gelir', 'Restoran Payı', 'Bayi Kom.', 'Kurye', 'Bayi Tes.'],
            $settlements->map(fn($s) => [
                $s->id,
                $s->branch->name ?? '-',
                $s->restaurant_name,
                $s->order_count,
                number_format($s->total_revenue, 2) . ' ₺',
                number_format($s->restaurant_share, 2) . ' ₺',
                number_format($s->branch_commission, 2) . ' ₺',
                number_format($s->courier_earnings, 2) . ' ₺',
                number_format($s->branch_delivery_share, 2) . ' ₺',
            ])
        );

        // Özet
        $this->info('');
        $this->info('─────────────────────────────────────────');
        $this->info('ÖZET');
        $this->info('─────────────────────────────────────────');

        $totalOrders = $settlements->sum('order_count');
        $totalRevenue = $settlements->sum('total_revenue');
        $totalRestaurantShare = $settlements->sum('restaurant_share');
        $totalBranchCommission = $settlements->sum('branch_commission');
        $totalCourierEarnings = $settlements->sum('courier_earnings');
        $totalBranchDeliveryShare = $settlements->sum('branch_delivery_share');

        $this->line("Toplam Sipariş: {$totalOrders}");
        $this->line("Toplam Gelir: " . number_format($totalRevenue, 2) . ' ₺');
        $this->line("Restoran Ödemesi: " . number_format($totalRestaurantShare, 2) . ' ₺');
        $this->line("Bayi Komisyonu: " . number_format($totalBranchCommission, 2) . ' ₺');
        $this->line("Kurye Kazancı: " . number_format($totalCourierEarnings, 2) . ' ₺');
        $this->line("Bayi Teslimat Payı: " . number_format($totalBranchDeliveryShare, 2) . ' ₺');
        $this->info("Toplam Bayi Kazancı: " . number_format($totalBranchCommission + $totalBranchDeliveryShare, 2) . ' ₺');

        $this->info('');
        $this->info('Settlement\'lar başarıyla oluşturuldu.');
    }

    /**
     * Dry run - hesapla ama kaydetme
     */
    private function dryRunCalculation(Carbon $date, ?int $branchId): void
    {
        // Siparişleri çek
        $query = \App\Models\Order::where('status', \App\Models\Order::STATUS_DELIVERED)
            ->whereDate('delivered_at', $date)
            ->whereNull('settlement_id')
            ->with(['branch', 'restaurantConnection']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {
            $this->warn('Bu tarih için hesaplanacak sipariş bulunamadı.');
            return;
        }

        $this->info("Hesaplanacak Sipariş Sayısı: {$orders->count()}");
        $this->info('');

        // Branch'lere göre grupla
        $byBranch = $orders->groupBy('branch_id');

        foreach ($byBranch as $branchIdKey => $branchOrders) {
            $branch = $branchOrders->first()->branch;
            $this->info("Branch: {$branch->name}");
            $this->info('─────────────────────────────────────────');

            // Restoranlara göre grupla
            $byRestaurant = $branchOrders->groupBy('restaurant_connection_id');

            foreach ($byRestaurant as $restaurantId => $restaurantOrders) {
                $connection = $restaurantOrders->first()->restaurantConnection;
                $restaurantName = $connection?->external_restaurant_name ?? 'İç Siparişler';

                $totalRevenue = $restaurantOrders->sum('subtotal');
                $totalDeliveryFee = $restaurantOrders->sum('delivery_fee');

                // Dağılım hesapla
                $distribution = $this->settlementService->calculateOrderDistribution($restaurantOrders->first());

                // Toplam için oranla
                $orderCount = $restaurantOrders->count();

                $this->line("  Restoran: {$restaurantName}");
                $this->line("    Sipariş Sayısı: {$orderCount}");
                $this->line("    Toplam Gelir: " . number_format($totalRevenue, 2) . ' ₺');
                $this->line("    Teslimat Ücreti: " . number_format($totalDeliveryFee, 2) . ' ₺');
                $this->line("    (Detaylı dağılım dry-run'da hesaplanmıyor)");
                $this->line('');
            }
        }

        $this->warn('DRY RUN tamamlandı. Hiçbir değişiklik kaydedilmedi.');
    }
}
