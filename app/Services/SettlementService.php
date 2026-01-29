<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchSetting;
use App\Models\DailySettlement;
use App\Models\Order;
use App\Models\RestaurantConnection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettlementService
{
    /**
     * Default oranlar (BranchSetting yoksa kullanılır)
     */
    private const DEFAULT_COMMISSION_RATE = 5.00;  // %5
    private const DEFAULT_COURIER_RATE = 60.00;    // %60

    /**
     * Belirli bir tarih için tüm branch'lerin settlement'larını hesapla
     */
    public function calculateForDate(Carbon $date, ?int $branchId = null): Collection
    {
        $settlements = collect();

        // Hangi branch'ler için hesaplama yapılacak
        $branchQuery = Branch::where('is_active', true);
        if ($branchId) {
            $branchQuery->where('id', $branchId);
        }

        $branches = $branchQuery->get();

        foreach ($branches as $branch) {
            $branchSettlements = $this->calculateForBranch($branch, $date);
            $settlements = $settlements->merge($branchSettlements);
        }

        return $settlements;
    }

    /**
     * Tek bir branch için günlük settlement hesapla
     */
    public function calculateForBranch(Branch $branch, Carbon $date): Collection
    {
        $settlements = collect();

        // Teslim edilmiş ve henüz settlement'a dahil edilmemiş siparişleri çek
        $orders = Order::where('branch_id', $branch->id)
            ->where('status', Order::STATUS_DELIVERED)
            ->whereDate('delivered_at', $date)
            ->whereNull('settlement_id')
            ->get();

        if ($orders->isEmpty()) {
            return $settlements;
        }

        // Siparişleri restaurant_connection_id'ye göre grupla
        $groupedOrders = $orders->groupBy('restaurant_connection_id');

        // Branch ayarlarını al
        $settings = BranchSetting::getOrCreateForBranch($branch->id);
        $defaultCommissionRate = $settings->restaurant_commission_rate ?? self::DEFAULT_COMMISSION_RATE;
        $defaultCourierRate = $settings->courier_fee_percentage ?? self::DEFAULT_COURIER_RATE;

        foreach ($groupedOrders as $restaurantConnectionId => $restaurantOrders) {
            $settlement = $this->createSettlement(
                $branch,
                $restaurantConnectionId ?: null,
                $date,
                $restaurantOrders,
                $defaultCommissionRate,
                $defaultCourierRate
            );

            if ($settlement) {
                $settlements->push($settlement);
            }
        }

        return $settlements;
    }

    /**
     * Settlement kaydı oluştur
     */
    protected function createSettlement(
        Branch $branch,
        ?int $restaurantConnectionId,
        Carbon $date,
        Collection $orders,
        float $defaultCommissionRate,
        float $defaultCourierRate
    ): ?DailySettlement {
        // Daha önce aynı kombinasyon için settlement var mı kontrol et
        $existing = DailySettlement::where('branch_id', $branch->id)
            ->where('settlement_date', $date)
            ->where(function ($q) use ($restaurantConnectionId) {
                if ($restaurantConnectionId === null) {
                    $q->whereNull('restaurant_connection_id');
                } else {
                    $q->where('restaurant_connection_id', $restaurantConnectionId);
                }
            })
            ->first();

        if ($existing && $existing->status !== DailySettlement::STATUS_CANCELLED) {
            Log::warning('Settlement already exists', [
                'branch_id' => $branch->id,
                'restaurant_connection_id' => $restaurantConnectionId,
                'date' => $date->format('Y-m-d'),
            ]);
            return null;
        }

        // Komisyon oranını belirle (restoran özel oranı varsa onu kullan)
        $commissionRate = $this->getCommissionRate($restaurantConnectionId, $defaultCommissionRate);
        $courierRate = $defaultCourierRate;

        // Toplam tutarları hesapla
        $totalRevenue = $orders->sum('subtotal');
        $deliveryFeeTotal = $orders->sum('delivery_fee');
        $orderCount = $orders->count();
        $orderIds = $orders->pluck('id')->toArray();

        // Dağılım hesapla
        $distribution = $this->calculateDistribution(
            $totalRevenue,
            $deliveryFeeTotal,
            $commissionRate,
            $courierRate,
            $restaurantConnectionId !== null
        );

        try {
            return DB::transaction(function () use (
                $branch,
                $restaurantConnectionId,
                $date,
                $orderCount,
                $totalRevenue,
                $deliveryFeeTotal,
                $distribution,
                $commissionRate,
                $courierRate,
                $orderIds
            ) {
                // Settlement kaydını oluştur
                $settlement = DailySettlement::create([
                    'branch_id' => $branch->id,
                    'restaurant_connection_id' => $restaurantConnectionId,
                    'settlement_date' => $date,
                    'order_count' => $orderCount,
                    'total_revenue' => $totalRevenue,
                    'delivery_fee_total' => $deliveryFeeTotal,
                    'restaurant_share' => $distribution['restaurant_share'],
                    'branch_commission' => $distribution['branch_commission'],
                    'courier_earnings' => $distribution['courier_earnings'],
                    'branch_delivery_share' => $distribution['branch_delivery_share'],
                    'commission_rate_used' => $commissionRate,
                    'courier_rate_used' => $courierRate,
                    'status' => DailySettlement::STATUS_PENDING,
                    'order_ids' => $orderIds,
                ]);

                // Siparişlere settlement_id yaz
                Order::whereIn('id', $orderIds)->update(['settlement_id' => $settlement->id]);

                Log::info('Settlement created', [
                    'settlement_id' => $settlement->id,
                    'branch' => $branch->name,
                    'restaurant_connection_id' => $restaurantConnectionId,
                    'date' => $date->format('Y-m-d'),
                    'order_count' => $orderCount,
                    'total_revenue' => $totalRevenue,
                ]);

                return $settlement;
            });
        } catch (\Exception $e) {
            Log::error('Settlement creation failed', [
                'branch_id' => $branch->id,
                'restaurant_connection_id' => $restaurantConnectionId,
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Gelir dağılımını hesapla
     */
    public function calculateDistribution(
        float $totalRevenue,
        float $deliveryFeeTotal,
        float $commissionRate,
        float $courierRate,
        bool $isRestaurantOrder
    ): array {
        $commissionMultiplier = $commissionRate / 100;
        $courierMultiplier = $courierRate / 100;

        if ($isRestaurantOrder) {
            // Restoran siparişi - komisyon kes
            $restaurantShare = $totalRevenue * (1 - $commissionMultiplier);
            $branchCommission = $totalRevenue * $commissionMultiplier;
        } else {
            // İç sipariş - tüm gelir bayinin
            $restaurantShare = 0;
            $branchCommission = $totalRevenue;
        }

        // Teslimat ücreti dağılımı
        $courierEarnings = $deliveryFeeTotal * $courierMultiplier;
        $branchDeliveryShare = $deliveryFeeTotal * (1 - $courierMultiplier);

        return [
            'restaurant_share' => round($restaurantShare, 2),
            'branch_commission' => round($branchCommission, 2),
            'courier_earnings' => round($courierEarnings, 2),
            'branch_delivery_share' => round($branchDeliveryShare, 2),
        ];
    }

    /**
     * Tek sipariş için dağılım hesapla (önizleme için)
     */
    public function calculateOrderDistribution(Order $order): array
    {
        $settings = BranchSetting::getOrCreateForBranch($order->branch_id);
        $commissionRate = $this->getCommissionRate(
            $order->restaurant_connection_id,
            $settings->restaurant_commission_rate ?? self::DEFAULT_COMMISSION_RATE
        );
        $courierRate = $settings->courier_fee_percentage ?? self::DEFAULT_COURIER_RATE;

        return $this->calculateDistribution(
            (float) $order->subtotal,
            (float) $order->delivery_fee,
            $commissionRate,
            $courierRate,
            $order->restaurant_connection_id !== null
        );
    }

    /**
     * Komisyon oranını belirle
     * Önce restoran özel oranına bak, yoksa branch default'unu kullan
     */
    public function getCommissionRate(?int $restaurantConnectionId, float $defaultRate): float
    {
        if (!$restaurantConnectionId) {
            return 0; // İç siparişlerde komisyon yok (tüm gelir bayinin)
        }

        $connection = RestaurantConnection::find($restaurantConnectionId);
        if ($connection && isset($connection->settings['commission_rate'])) {
            return (float) $connection->settings['commission_rate'];
        }

        return $defaultRate;
    }

    /**
     * Settlement'ı onayla
     */
    public function approveSettlement(DailySettlement $settlement): bool
    {
        return $settlement->approve();
    }

    /**
     * Settlement'ı ödendi olarak işaretle
     */
    public function markAsPaid(DailySettlement $settlement, ?string $notes = null): bool
    {
        return $settlement->markAsPaid($notes);
    }

    /**
     * Settlement'ı iptal et
     */
    public function cancelSettlement(DailySettlement $settlement, ?string $reason = null): bool
    {
        return $settlement->cancel($reason);
    }

    /**
     * Tarih aralığı için özet rapor
     */
    public function getSummaryReport(int $branchId, Carbon $startDate, Carbon $endDate): array
    {
        $settlements = DailySettlement::where('branch_id', $branchId)
            ->whereBetween('settlement_date', [$startDate, $endDate])
            ->whereIn('status', [DailySettlement::STATUS_PENDING, DailySettlement::STATUS_APPROVED, DailySettlement::STATUS_PAID])
            ->get();

        $totalOrders = $settlements->sum('order_count');
        $totalRevenue = $settlements->sum('total_revenue');
        $totalDeliveryFees = $settlements->sum('delivery_fee_total');
        $totalRestaurantShare = $settlements->sum('restaurant_share');
        $totalBranchCommission = $settlements->sum('branch_commission');
        $totalCourierEarnings = $settlements->sum('courier_earnings');
        $totalBranchDeliveryShare = $settlements->sum('branch_delivery_share');

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'settlement_count' => $settlements->count(),
            'order_count' => $totalOrders,
            'total_revenue' => round($totalRevenue, 2),
            'total_delivery_fees' => round($totalDeliveryFees, 2),
            'restaurant_payable' => round($totalRestaurantShare, 2),
            'branch_commission' => round($totalBranchCommission, 2),
            'courier_earnings' => round($totalCourierEarnings, 2),
            'branch_delivery_share' => round($totalBranchDeliveryShare, 2),
            'total_branch_earnings' => round($totalBranchCommission + $totalBranchDeliveryShare, 2),
            'by_status' => [
                'pending' => $settlements->where('status', DailySettlement::STATUS_PENDING)->count(),
                'approved' => $settlements->where('status', DailySettlement::STATUS_APPROVED)->count(),
                'paid' => $settlements->where('status', DailySettlement::STATUS_PAID)->count(),
            ],
        ];
    }
}
