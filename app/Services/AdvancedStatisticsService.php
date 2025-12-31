<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Courier;
use App\Models\Branch;
use App\Models\PricingPolicy;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdvancedStatisticsService
{
    protected $startDate;
    protected $endDate;

    public function __construct(?Carbon $startDate = null, ?Carbon $endDate = null)
    {
        $this->startDate = $startDate ?? now()->subDays(30);
        $this->endDate = $endDate ?? now();
    }

    /**
     * Get all advanced statistics
     */
    public function getAllStatistics(): array
    {
        return [
            'kpi' => $this->getKPIMetrics(),
            'operational_averages' => $this->getOperationalAverages(),
            'km_analysis' => $this->getKMAnalysis(),
            'cancellation_analysis' => $this->getCancellationAnalysis(),
            'time_performance' => $this->getTimePerformance(),
            'platform_analysis' => $this->getPlatformAnalysis(),
            'payment_method_analysis' => $this->getPaymentMethodAnalysis(),
            'branch_earnings' => $this->getBranchEarningsAnalysis(),
            'courier_earnings' => $this->getCourierEarningsAnalysis(),
            'geographic_analysis' => $this->getGeographicAnalysis(),
            'business_orders' => $this->getBusinessOrdersAnalysis(),
        ];
    }

    /**
     * KPI Metrics
     */
    public function getKPIMetrics(): array
    {
        $orders = Order::whereBetween('created_at', [$this->startDate, $this->endDate]);

        // Bayi paketleri (kurye tarafından teslim edilen)
        $deliveredOrders = (clone $orders)->where('status', Order::STATUS_DELIVERED)
            ->whereNotNull('courier_id')
            ->get();

        // İşletme paketleri (işletmenin kendi kuryeleriyle teslim edilen)
        $businessOrders = (clone $orders)->where('status', Order::STATUS_DELIVERED)
            ->whereNull('courier_id')
            ->get();

        $totalDistance = $deliveredOrders->sum('delivery_distance') ?: 0;
        $totalRevenue = $deliveredOrders->sum('delivery_fee') ?: 0;

        // Kurye ödemesi hesaplama (PricingPolicy'den dinamik olarak hesaplanıyor)
        $courierPayment = $deliveredOrders->sum(function ($order) {
            return PricingPolicy::calculateCourierEarnings($order);
        });

        $netProfit = $totalRevenue - $courierPayment;

        return [
            'total_delivered' => $deliveredOrders->count(),
            'total_business_orders' => $businessOrders->count(),
            'total_distance' => round($totalDistance, 2),
            'total_revenue' => $totalRevenue,
            'courier_payment' => $courierPayment,
            'net_profit' => $netProfit,
        ];
    }

    /**
     * Operational Averages
     */
    public function getOperationalAverages(): array
    {
        $deliveredOrders = Order::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where('status', Order::STATUS_DELIVERED)
            ->whereNotNull('delivered_at')
            ->get();

        $avgDeliveryTime = 0;
        $avgPickupTime = 0;
        $avgPrepTime = 0;
        $count = $deliveredOrders->count();

        if ($count > 0) {
            $totalDeliveryTime = 0;
            $totalPickupTime = 0;
            $totalPrepTime = 0;

            foreach ($deliveredOrders as $order) {
                // Toplam teslimat süresi (sipariş oluşturma - teslimat)
                if ($order->delivered_at && $order->created_at) {
                    $totalDeliveryTime += $order->created_at->diffInMinutes($order->delivered_at);
                }

                // Paket alma süresi (sipariş hazır - kurye aldı)
                if ($order->picked_up_at && $order->prepared_at) {
                    $totalPickupTime += $order->prepared_at->diffInMinutes($order->picked_up_at);
                }

                // Hazırlık süresi (sipariş kabul - hazır)
                if ($order->prepared_at && $order->accepted_at) {
                    $totalPrepTime += $order->accepted_at->diffInMinutes($order->prepared_at);
                }
            }

            $avgDeliveryTime = round($totalDeliveryTime / $count, 1);
            $avgPickupTime = round($totalPickupTime / max($deliveredOrders->whereNotNull('picked_up_at')->count(), 1), 1);
            $avgPrepTime = round($totalPrepTime / max($deliveredOrders->whereNotNull('prepared_at')->count(), 1), 1);
        }

        return [
            'avg_delivery_time' => $avgDeliveryTime,
            'avg_pickup_time' => $avgPickupTime,
            'avg_prep_time' => $avgPrepTime,
        ];
    }

    /**
     * KM Analysis
     */
    public function getKMAnalysis(): array
    {
        $orders = Order::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where('status', Order::STATUS_DELIVERED)
            ->get();

        // KM bazlı ücretler PricingPolicy'den dinamik olarak hesaplanıyor
        $businessKmFee = $orders->sum(function ($order) {
            return PricingPolicy::calculateKmEarnings($order, 'business');
        });

        $courierKmFee = $orders->sum(function ($order) {
            return PricingPolicy::calculateKmEarnings($order, 'courier');
        });

        return [
            'business_km_fee' => $businessKmFee,
            'courier_km_fee' => $courierKmFee,
            'total_km_earning' => $businessKmFee + $courierKmFee,
        ];
    }

    /**
     * Cancellation Analysis
     */
    public function getCancellationAnalysis(): array
    {
        $cancelledOrders = Order::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where('status', Order::STATUS_CANCELLED)
            ->get();

        $standardCancels = $cancelledOrders->where('cancel_reason', '!=', 'paid_cancel')->count();
        $paidCancels = $cancelledOrders->where('cancel_reason', 'paid_cancel')->count();
        $total = $cancelledOrders->count();

        return [
            'standard_cancels' => $standardCancels,
            'paid_cancels' => $paidCancels,
            'total_cancels' => $total,
            'standard_ratio' => $total > 0 ? round(($standardCancels / $total) * 100, 1) : 0,
            'paid_ratio' => $total > 0 ? round(($paidCancels / $total) * 100, 1) : 0,
            'cancellation_reasons' => $this->getCancellationReasons(),
        ];
    }

    /**
     * Get cancellation reasons breakdown
     */
    protected function getCancellationReasons(): array
    {
        return Order::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where('status', Order::STATUS_CANCELLED)
            ->whereNotNull('cancel_reason')
            ->select('cancel_reason', DB::raw('count(*) as count'))
            ->groupBy('cancel_reason')
            ->get()
            ->pluck('count', 'cancel_reason')
            ->toArray();
    }

    /**
     * Time Performance (Hourly & Daily)
     */
    public function getTimePerformance(): array
    {
        return [
            'hourly' => $this->getHourlyPerformance(),
            'daily' => $this->getDailyPerformance(),
            'heatmap' => $this->getDeliveryHeatmap(),
        ];
    }

    /**
     * Get hourly performance
     */
    protected function getHourlyPerformance(): array
    {
        $hourly = [];
        $driver = DB::connection()->getDriverName();

        for ($i = 0; $i < 24; $i++) {
            if ($driver === 'sqlite') {
                $hourly[$i] = Order::whereBetween('created_at', [$this->startDate, $this->endDate])
                    ->whereRaw('CAST(strftime("%H", created_at) AS INTEGER) = ?', [$i])
                    ->count();
            } else {
                $hourly[$i] = Order::whereBetween('created_at', [$this->startDate, $this->endDate])
                    ->whereRaw('HOUR(created_at) = ?', [$i])
                    ->count();
            }
        }
        return $hourly;
    }

    /**
     * Get daily performance
     */
    protected function getDailyPerformance(): array
    {
        $daily = [];
        $dates = [];

        $start = $this->startDate->copy();
        while ($start->lte($this->endDate)) {
            $dateStr = $start->format('Y-m-d');
            $dates[] = $start->format('d M');
            $daily[] = Order::whereDate('created_at', $dateStr)->count();
            $start->addDay();
        }

        return [
            'dates' => $dates,
            'counts' => $daily,
        ];
    }

    /**
     * Get delivery heatmap (hour x day of week)
     */
    protected function getDeliveryHeatmap(): array
    {
        $heatmap = [];
        $driver = DB::connection()->getDriverName();

        // Days: 0 = Sunday, 1 = Monday, ..., 6 = Saturday
        // Turkish days: Pzt (1), Sal (2), Çrş (3), Prş (4), Cum (5), Cmt (6), Pzr (0)
        $dayMapping = [1, 2, 3, 4, 5, 6, 0]; // Start with Monday

        foreach ($dayMapping as $day) {
            $dayData = [];
            for ($hour = 0; $hour < 24; $hour++) {
                if ($driver === 'sqlite') {
                    // SQLite: strftime('%w', date) returns 0=Sunday, 1=Monday, ..., 6=Saturday
                    $count = Order::whereBetween('created_at', [$this->startDate, $this->endDate])
                        ->whereRaw('CAST(strftime("%w", created_at) AS INTEGER) = ?', [$day])
                        ->whereRaw('CAST(strftime("%H", created_at) AS INTEGER) = ?', [$hour])
                        ->count();
                } else {
                    // MySQL: DAYOFWEEK returns 1=Sunday, 2=Monday, ..., 7=Saturday
                    $count = Order::whereBetween('created_at', [$this->startDate, $this->endDate])
                        ->whereRaw('DAYOFWEEK(created_at) = ?', [$day + 1])
                        ->whereRaw('HOUR(created_at) = ?', [$hour])
                        ->count();
                }
                $dayData[] = $count;
            }
            $heatmap[] = $dayData;
        }

        return $heatmap;
    }

    /**
     * Platform Analysis
     */
    public function getPlatformAnalysis(): array
    {
        // Bu field'ın Order modelinde olduğunu varsayıyoruz
        // Eğer yoksa migration ile eklenmelidir
        $platforms = Order::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->select('platform', DB::raw('count(*) as count'), DB::raw('sum(total) as revenue'))
            ->groupBy('platform')
            ->get();

        $total = $platforms->sum('count');
        $totalRevenue = $platforms->sum('revenue');

        $data = [];
        foreach ($platforms as $platform) {
            $data[] = [
                'platform' => $platform->platform ?? 'Manual',
                'count' => $platform->count,
                'revenue' => $platform->revenue,
                'ratio' => $total > 0 ? round(($platform->count / $total) * 100, 1) : 0,
            ];
        }

        return [
            'platforms' => $data,
            'total_count' => $total,
            'total_revenue' => $totalRevenue,
        ];
    }

    /**
     * Payment Method Analysis
     */
    public function getPaymentMethodAnalysis(): array
    {
        $methods = Order::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where('status', Order::STATUS_DELIVERED)
            ->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(total) as revenue'))
            ->groupBy('payment_method')
            ->get();

        $total = $methods->sum('count');

        $data = [];
        foreach ($methods as $method) {
            $data[] = [
                'method' => $this->getPaymentMethodLabel($method->payment_method),
                'count' => $method->count,
                'revenue' => $method->revenue,
                'ratio' => $total > 0 ? round(($method->count / $total) * 100, 1) : 0,
            ];
        }

        return [
            'methods' => $data,
            'total' => $total,
        ];
    }

    /**
     * Branch Earnings Analysis
     */
    public function getBranchEarningsAnalysis(): array
    {
        $branches = Branch::with(['orders' => function ($q) {
            $q->whereBetween('created_at', [$this->startDate, $this->endDate])
                ->where('status', Order::STATUS_DELIVERED);
        }])->get();

        $data = [];
        foreach ($branches as $branch) {
            $orders = $branch->orders;
            $grossRevenue = $orders->sum('delivery_fee');

            // Net gelir hesaplama (PricingPolicy'den dinamik olarak hesaplanıyor)
            $netRevenue = $orders->sum(function ($order) {
                return PricingPolicy::calculateBranchEarnings($order);
            });

            $data[] = [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'order_count' => $orders->count(),
                'gross_revenue' => $grossRevenue,
                'net_revenue' => $netRevenue,
                'pricing_policy' => $this->getBranchPricingPolicy($branch),
            ];
        }

        // Sort by order count
        usort($data, function ($a, $b) {
            return $b['order_count'] - $a['order_count'];
        });

        return $data;
    }

    /**
     * Courier Earnings Analysis
     */
    public function getCourierEarningsAnalysis(): array
    {
        $couriers = Courier::with(['orders' => function ($q) {
            $q->whereBetween('created_at', [$this->startDate, $this->endDate])
                ->where('status', Order::STATUS_DELIVERED);
        }])->get();

        $data = [];
        foreach ($couriers as $courier) {
            $orders = $courier->orders;

            // Kurye kazançlarını PricingPolicy'den dinamik olarak hesapla
            $baseEarning = $orders->sum(function ($order) {
                return PricingPolicy::calculateCourierEarnings($order);
            });

            // Bonus/kesinti hesaplamaları
            $bonuses = 0;
            $totalEarning = $baseEarning + $bonuses;

            // Kurye için aktif fiyatlandırma politikasını al
            $pricing = PricingPolicy::getPricingForCourier($courier->id);

            $data[] = [
                'courier_id' => $courier->id,
                'courier_name' => $courier->name,
                'order_count' => $orders->count(),
                'base_earning' => $baseEarning,
                'bonuses' => $bonuses,
                'total_earning' => $totalEarning,
                'pricing_policy' => $pricing['policy_name'],
            ];
        }

        // Sort by order count
        usort($data, function ($a, $b) {
            return $b['order_count'] - $a['order_count'];
        });

        return $data;
    }

    /**
     * Geographic Analysis
     */
    public function getGeographicAnalysis(): array
    {
        $deliveryPoints = Order::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where('status', Order::STATUS_DELIVERED)
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->select('lat', 'lng', 'customer_address', 'delivery_distance')
            ->get();

        // Cluster analysis (basit grid-based clustering)
        $clusters = $this->clusterDeliveryPoints($deliveryPoints);

        return [
            'delivery_points' => $deliveryPoints->map(function ($point) {
                return [
                    'lat' => (float) $point->lat,
                    'lng' => (float) $point->lng,
                    'address' => $point->customer_address,
                ];
            })->toArray(),
            'clusters' => $clusters,
            'total_points' => $deliveryPoints->count(),
            'coverage_area' => $this->calculateCoverageArea($deliveryPoints),
        ];
    }

    /**
     * Business Orders Analysis
     */
    public function getBusinessOrdersAnalysis(): array
    {
        // İşletmenin kendi kuryeleriyle teslim ettiği siparişler
        $businessOrders = Order::whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where('status', Order::STATUS_DELIVERED)
            ->whereNull('courier_id')
            ->get();

        $paymentMethods = $businessOrders->groupBy('payment_method')->map(function ($orders, $method) {
            return [
                'method' => $this->getPaymentMethodLabel($method),
                'count' => $orders->count(),
                'revenue' => $orders->sum('total'),
            ];
        })->values()->toArray();

        return [
            'total_orders' => $businessOrders->count(),
            'total_revenue' => $businessOrders->sum('total'),
            'payment_methods' => $paymentMethods,
        ];
    }

    /**
     * Helper: Get payment method label
     */
    protected function getPaymentMethodLabel(string $method): string
    {
        return match ($method) {
            'cash' => 'Nakit',
            'card' => 'Kredi Kartı',
            'online' => 'Online',
            default => $method,
        };
    }

    /**
     * Helper: Get branch pricing policy
     */
    protected function getBranchPricingPolicy(Branch $branch): string
    {
        $policy = $branch->pricingPolicies()->where('is_active', true)->first();
        return $policy ? $policy->name : 'Standart Politika';
    }

    /**
     * Helper: Cluster delivery points
     */
    protected function clusterDeliveryPoints($points): array
    {
        // Basit grid-based clustering
        $gridSize = 0.01; // ~1km
        $grid = [];

        foreach ($points as $point) {
            $gridX = floor($point->lat / $gridSize);
            $gridY = floor($point->lng / $gridSize);
            $key = "{$gridX},{$gridY}";

            if (!isset($grid[$key])) {
                $grid[$key] = [
                    'lat' => $point->lat,
                    'lng' => $point->lng,
                    'count' => 0,
                ];
            }
            $grid[$key]['count']++;
        }

        return array_values($grid);
    }

    /**
     * Helper: Calculate coverage area
     */
    protected function calculateCoverageArea($points): float
    {
        if ($points->isEmpty()) {
            return 0;
        }

        $lats = $points->pluck('lat')->toArray();
        $lngs = $points->pluck('lng')->toArray();

        $minLat = min($lats);
        $maxLat = max($lats);
        $minLng = min($lngs);
        $maxLng = max($lngs);

        // Approximate area in km²
        $latDiff = ($maxLat - $minLat) * 111; // 1 degree lat ≈ 111 km
        $lngDiff = ($maxLng - $minLng) * 111 * cos(deg2rad(($minLat + $maxLat) / 2));

        return round($latDiff * $lngDiff, 2);
    }

    /**
     * Get top performers
     */
    public function getTopPerformers(): array
    {
        $topCouriers = Courier::withCount(['orders' => function ($q) {
            $q->whereBetween('created_at', [$this->startDate, $this->endDate])
                ->where('status', Order::STATUS_DELIVERED);
        }])
            ->orderBy('orders_count', 'desc')
            ->take(10)
            ->get();

        $topBranches = Branch::withCount(['orders' => function ($q) {
            $q->whereBetween('created_at', [$this->startDate, $this->endDate])
                ->where('status', Order::STATUS_DELIVERED);
        }])
            ->orderBy('orders_count', 'desc')
            ->take(10)
            ->get();

        return [
            'top_couriers' => $topCouriers,
            'top_branches' => $topBranches,
        ];
    }
}
