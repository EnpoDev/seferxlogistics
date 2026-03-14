<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Courier;
use App\Models\Branch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    /**
     * Apply branch filtering to a query
     */
    private function applyBranchFilter($query, ?int $branchId, ?array $userBranchIds = null)
    {
        if ($branchId) {
            $query->where('branch_id', $branchId);
        } elseif ($userBranchIds) {
            $query->whereIn('branch_id', $userBranchIds);
        }

        return $query;
    }

    /**
     * Calculate payment breakdown supporting split payments
     */
    private function calculatePaymentBreakdown(Collection $orders): array
    {
        $breakdown = [];

        foreach ($orders as $order) {
            if ($order->hasSplitPayment()) {
                foreach ($order->payment_methods as $pm) {
                    $method = $pm['method'] ?? 'unknown';
                    if (!isset($breakdown[$method])) {
                        $breakdown[$method] = ['count' => 0, 'total' => 0];
                    }
                    $breakdown[$method]['count']++;
                    $breakdown[$method]['total'] += $pm['amount'] ?? 0;
                }
            } else {
                $method = $order->payment_method ?? 'unknown';
                if (!isset($breakdown[$method])) {
                    $breakdown[$method] = ['count' => 0, 'total' => 0];
                }
                $breakdown[$method]['count']++;
                $breakdown[$method]['total'] += $order->total ?? 0;
            }
        }

        return $breakdown;
    }

    /**
     * Genel finansal özet
     */
    public function getFinancialSummary(Carbon $startDate, Carbon $endDate, ?int $branchId = null, ?array $userBranchIds = null): array
    {
        $query = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'delivered');

        $this->applyBranchFilter($query, $branchId, $userBranchIds);

        $orders = $query->get();

        $totalRevenue = $orders->sum('total');
        $totalDeliveryFees = $orders->sum('delivery_fee');
        $totalSubtotal = $orders->sum('subtotal');
        $orderCount = $orders->count();

        // Ödeme yöntemine göre dağılım (split payment destekli)
        $paymentBreakdown = $this->calculatePaymentBreakdown($orders);

        // Günlük ortalama
        $daysDiff = max(1, $startDate->diffInDays($endDate));
        $dailyAverage = $totalRevenue / $daysDiff;

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'days' => $daysDiff,
            ],
            'revenue' => [
                'total' => round($totalRevenue, 2),
                'subtotal' => round($totalSubtotal, 2),
                'delivery_fees' => round($totalDeliveryFees, 2),
                'daily_average' => round($dailyAverage, 2),
            ],
            'orders' => [
                'count' => $orderCount,
                'average_value' => $orderCount > 0 ? round($totalRevenue / $orderCount, 2) : 0,
            ],
            'payment_breakdown' => $paymentBreakdown,
        ];
    }

    /**
     * Günlük gelir raporu
     */
    public function getDailyRevenueReport(Carbon $startDate, Carbon $endDate, ?int $branchId = null, ?array $userBranchIds = null): Collection
    {
        $query = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'delivered');

        $this->applyBranchFilter($query, $branchId, $userBranchIds);

        $orders = $query->get();

        return $orders->groupBy(function ($order) {
            return $order->created_at->format('Y-m-d');
        })->map(function ($dayOrders, $date) {
            $breakdown = $this->calculatePaymentBreakdown($dayOrders);

            return [
                'date' => $date,
                'order_count' => $dayOrders->count(),
                'total_revenue' => round($dayOrders->sum('total'), 2),
                'delivery_fees' => round($dayOrders->sum('delivery_fee'), 2),
                'cash' => round($breakdown['cash']['total'] ?? 0, 2),
                'card' => round($breakdown['card']['total'] ?? 0, 2),
                'online' => round($breakdown['online']['total'] ?? 0, 2),
                'other' => round(
                    collect($breakdown)
                        ->except(['cash', 'card', 'online'])
                        ->sum('total'),
                    2
                ),
            ];
        })->sortKeys()->values();
    }

    /**
     * Kurye performans ve kazanç raporu
     */
    public function getCourierEarningsReport(Carbon $startDate, Carbon $endDate, ?int $branchId = null, ?array $userBranchIds = null): Collection
    {
        $query = Order::whereBetween('delivered_at', [$startDate, $endDate])
            ->where('status', 'delivered')
            ->whereNotNull('courier_id')
            ->with('courier');

        $this->applyBranchFilter($query, $branchId, $userBranchIds);

        $orders = $query->get();

        return $orders->groupBy('courier_id')->map(function ($courierOrders) {
            $courier = $courierOrders->first()->courier;
            $deliveryCount = $courierOrders->count();

            // Ortalama teslimat süresi
            $avgDeliveryTime = $courierOrders->avg(function ($order) {
                if ($order->picked_up_at && $order->delivered_at) {
                    return $order->picked_up_at->diffInMinutes($order->delivered_at);
                }
                return null;
            });

            // Toplanan nakit (split payment destekli)
            $cashCollected = 0;
            foreach ($courierOrders as $order) {
                if ($order->hasSplitPayment()) {
                    $cashCollected += $order->getPaymentMethodAmount('cash');
                } elseif ($order->payment_method === 'cash') {
                    $cashCollected += $order->total;
                }
            }

            return [
                'courier_id' => $courier?->id,
                'courier_name' => $courier?->name ?? 'Bilinmeyen',
                'delivery_count' => $deliveryCount,
                'total_earnings' => round($courierOrders->sum('delivery_fee'), 2),
                'cash_collected' => round($cashCollected, 2),
                'avg_delivery_time' => round($avgDeliveryTime ?? 0, 1),
                'daily_average' => round($deliveryCount / max(1, $courierOrders->min('delivered_at')?->diffInDays($courierOrders->max('delivered_at')) ?? 1), 1),
            ];
        })->sortByDesc('delivery_count')->values();
    }

    /**
     * Şube performans raporu
     */
    public function getBranchPerformanceReport(Carbon $startDate, Carbon $endDate, ?array $userBranchIds = null): Collection
    {
        $query = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Order::STATUS_DELIVERED)
            ->with('branch');

        if ($userBranchIds) {
            $query->whereIn('branch_id', $userBranchIds);
        }

        $orders = $query->get();

        return $orders->groupBy('branch_id')->map(function ($branchOrders) use ($startDate, $endDate) {
            $branch = $branchOrders->first()->branch;

            return [
                'branch_id' => $branch?->id,
                'branch_name' => $branch?->name ?? 'Ana Sube',
                'order_count' => $branchOrders->count(),
                'total_revenue' => round($branchOrders->sum('total'), 2),
                'delivery_fees' => round($branchOrders->sum('delivery_fee'), 2),
                'avg_order_value' => round($branchOrders->avg('total'), 2),
                'cancelled_count' => Order::where('branch_id', $branch?->id)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->where('status', Order::STATUS_CANCELLED)
                    ->count(),
            ];
        })->sortByDesc('total_revenue')->values();
    }

    /**
     * Saatlik sipariş dağılımı
     */
    public function getHourlyDistribution(Carbon $startDate, Carbon $endDate, ?int $branchId = null, ?array $userBranchIds = null): array
    {
        $query = Order::whereBetween('created_at', [$startDate, $endDate]);

        $this->applyBranchFilter($query, $branchId, $userBranchIds);

        $orders = $query->get();

        $hourlyData = [];
        for ($i = 0; $i < 24; $i++) {
            $hourlyData[$i] = ['hour' => sprintf('%02d:00', $i), 'count' => 0, 'revenue' => 0];
        }

        foreach ($orders as $order) {
            $hour = (int) $order->created_at->format('H');
            $hourlyData[$hour]['count']++;
            $hourlyData[$hour]['revenue'] += $order->total;
        }

        return array_values($hourlyData);
    }

    /**
     * Haftalık karşılaştırma
     */
    public function getWeeklyComparison(?int $branchId = null, ?array $userBranchIds = null): array
    {
        $thisWeekStart = now()->startOfWeek();
        $thisWeekEnd = now()->endOfWeek();
        $lastWeekStart = now()->subWeek()->startOfWeek();
        $lastWeekEnd = now()->subWeek()->endOfWeek();

        $thisWeek = $this->getFinancialSummary($thisWeekStart, $thisWeekEnd, $branchId, $userBranchIds);
        $lastWeek = $this->getFinancialSummary($lastWeekStart, $lastWeekEnd, $branchId, $userBranchIds);

        $revenueChange = $lastWeek['revenue']['total'] > 0
            ? (($thisWeek['revenue']['total'] - $lastWeek['revenue']['total']) / $lastWeek['revenue']['total']) * 100
            : 0;

        $orderChange = $lastWeek['orders']['count'] > 0
            ? (($thisWeek['orders']['count'] - $lastWeek['orders']['count']) / $lastWeek['orders']['count']) * 100
            : 0;

        return [
            'this_week' => $thisWeek,
            'last_week' => $lastWeek,
            'changes' => [
                'revenue_percent' => round($revenueChange, 1),
                'orders_percent' => round($orderChange, 1),
                'revenue_trend' => $revenueChange >= 0 ? 'up' : 'down',
                'orders_trend' => $orderChange >= 0 ? 'up' : 'down',
            ],
        ];
    }

    /**
     * Aylık karşılaştırma
     */
    public function getMonthlyComparison(?int $branchId = null, ?array $userBranchIds = null): array
    {
        $thisMonthStart = now()->startOfMonth();
        $thisMonthEnd = now()->endOfMonth();
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $thisMonth = $this->getFinancialSummary($thisMonthStart, $thisMonthEnd, $branchId, $userBranchIds);
        $lastMonth = $this->getFinancialSummary($lastMonthStart, $lastMonthEnd, $branchId, $userBranchIds);

        $revenueChange = $lastMonth['revenue']['total'] > 0
            ? (($thisMonth['revenue']['total'] - $lastMonth['revenue']['total']) / $lastMonth['revenue']['total']) * 100
            : 0;

        return [
            'this_month' => $thisMonth,
            'last_month' => $lastMonth,
            'changes' => [
                'revenue_percent' => round($revenueChange, 1),
                'revenue_trend' => $revenueChange >= 0 ? 'up' : 'down',
            ],
        ];
    }

    /**
     * En çok satan ürünler
     */
    public function getTopProducts(Carbon $startDate, Carbon $endDate, int $limit = 10, ?int $branchId = null, ?array $userBranchIds = null): Collection
    {
        $query = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'delivered')
            ->with('items.product');

        $this->applyBranchFilter($query, $branchId, $userBranchIds);

        $orders = $query->get();

        $productSales = collect();

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $productId = $item->product_id ?? $item->id;
                $productName = $item->product?->name ?? $item->name ?? 'Bilinmeyen Urun';

                if (!$productSales->has($productId)) {
                    $productSales[$productId] = [
                        'product_id' => $productId,
                        'product_name' => $productName,
                        'quantity' => 0,
                        'revenue' => 0,
                    ];
                }

                $existing = $productSales[$productId];
                $existing['quantity'] += $item->quantity ?? 1;
                $existing['revenue'] += ($item->price ?? 0) * ($item->quantity ?? 1);
                $productSales[$productId] = $existing;
            }
        }

        return $productSales->sortByDesc('quantity')->take($limit)->values();
    }

    /**
     * Nakit akış raporu (split payment destekli)
     */
    public function getCashFlowReport(Carbon $startDate, Carbon $endDate, ?int $branchId = null, ?array $userBranchIds = null): array
    {
        $query = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'delivered');

        $this->applyBranchFilter($query, $branchId, $userBranchIds);

        $orders = $query->get();

        $breakdown = $this->calculatePaymentBreakdown($orders);

        // Kurye ödemeleri (ayarlardan veya varsayılan %70)
        $courierPaymentRate = config('services.courier.payment_rate', 0.7);
        $courierPayments = $orders->sum('delivery_fee') * $courierPaymentRate;

        $totalIncome = collect($breakdown)->sum('total');

        return [
            'income' => [
                'cash' => round($breakdown['cash']['total'] ?? 0, 2),
                'card' => round($breakdown['card']['total'] ?? 0, 2),
                'online' => round($breakdown['online']['total'] ?? 0, 2),
                'pluxee' => round($breakdown['pluxee']['total'] ?? 0, 2),
                'edenred' => round($breakdown['edenred']['total'] ?? 0, 2),
                'multinet' => round($breakdown['multinet']['total'] ?? 0, 2),
                'metropol' => round($breakdown['metropol']['total'] ?? 0, 2),
                'tokenflex' => round($breakdown['tokenflex']['total'] ?? 0, 2),
                'setcard' => round($breakdown['setcard']['total'] ?? 0, 2),
                'total' => round($totalIncome, 2),
            ],
            'expenses' => [
                'courier_payments' => round($courierPayments, 2),
            ],
            'net_cash_flow' => round($totalIncome - $courierPayments, 2),
        ];
    }

    /**
     * Yemek maliyet raporu
     */
    public function getMealCostReport(Carbon $startDate, Carbon $endDate, ?int $branchId = null, ?array $userBranchIds = null): array
    {
        $query = \App\Models\CourierMealBenefit::whereBetween('benefit_date', [$startDate, $endDate])
            ->with(['courier', 'restaurant']);

        // Branch filtering via courier ownership
        if ($userBranchIds) {
            $courierIds = \App\Models\Courier::whereIn('user_id', function ($q) use ($userBranchIds) {
                $q->select('user_id')->from('branches')->whereIn('id', $userBranchIds);
            })->pluck('id');
            $query->whereIn('courier_id', $courierIds);
        }

        $benefits = $query->get();

        $totalBenefits = $benefits->count();
        $usedBenefits = $benefits->where('is_used', true)->count();
        $totalCost = $benefits->where('is_used', true)->sum('meal_value');

        // Summary
        $summary = [
            'total_benefits' => $totalBenefits,
            'used_benefits' => $usedBenefits,
            'usage_rate' => $totalBenefits > 0 ? round(($usedBenefits / $totalBenefits) * 100, 1) : 0,
            'total_cost' => round($totalCost, 2),
            'avg_meal_cost' => $usedBenefits > 0 ? round($totalCost / $usedBenefits, 2) : 0,
        ];

        // By courier
        $byCourier = $benefits->groupBy('courier_id')->map(function ($courierBenefits) {
            $courier = $courierBenefits->first()->courier;
            $used = $courierBenefits->where('is_used', true);
            return [
                'courier_name' => $courier?->name ?? 'Bilinmeyen',
                'total_benefits' => $courierBenefits->count(),
                'used' => $used->count(),
                'total_cost' => round($used->sum('meal_value'), 2),
            ];
        })->sortByDesc('total_cost')->values()->toArray();

        // By restaurant
        $byRestaurant = $benefits->where('is_used', true)->groupBy('restaurant_id')->map(function ($restBenefits) {
            $restaurant = $restBenefits->first()->restaurant;
            return [
                'restaurant_name' => $restaurant?->name ?? 'Belirtilmemis',
                'count' => $restBenefits->count(),
                'total_cost' => round($restBenefits->sum('meal_value'), 2),
            ];
        })->sortByDesc('total_cost')->values()->toArray();

        // By meal type
        $byMealType = $benefits->groupBy('meal_type')->map(function ($typeBenefits, $type) {
            $used = $typeBenefits->where('is_used', true);
            return [
                'meal_type' => $type,
                'total' => $typeBenefits->count(),
                'used' => $used->count(),
                'cost' => round($used->sum('meal_value'), 2),
            ];
        })->values()->toArray();

        // Daily trend
        $dailyTrend = $benefits->where('is_used', true)->groupBy(function ($b) {
            return $b->benefit_date->format('Y-m-d');
        })->map(function ($dayBenefits, $date) {
            return [
                'date' => $date,
                'count' => $dayBenefits->count(),
                'cost' => round($dayBenefits->sum('meal_value'), 2),
            ];
        })->sortKeys()->values()->toArray();

        return [
            'summary' => $summary,
            'by_courier' => $byCourier,
            'by_restaurant' => $byRestaurant,
            'by_meal_type' => $byMealType,
            'daily_trend' => $dailyTrend,
        ];
    }

    /**
     * Rapor dışa aktarma için veri hazırla
     */
    public function prepareExportData(string $reportType, Carbon $startDate, Carbon $endDate, ?int $branchId = null, ?array $userBranchIds = null): array
    {
        return match ($reportType) {
            'summary' => [
                'title' => 'Finansal Ozet Raporu',
                'data' => $this->getFinancialSummary($startDate, $endDate, $branchId, $userBranchIds),
            ],
            'daily' => [
                'title' => 'Gunluk Gelir Raporu',
                'data' => $this->getDailyRevenueReport($startDate, $endDate, $branchId, $userBranchIds)->toArray(),
            ],
            'courier' => [
                'title' => 'Kurye Kazanc Raporu',
                'data' => $this->getCourierEarningsReport($startDate, $endDate, $branchId, $userBranchIds)->toArray(),
            ],
            'branch' => [
                'title' => 'Sube Performans Raporu',
                'data' => $this->getBranchPerformanceReport($startDate, $endDate, $userBranchIds)->toArray(),
            ],
            'cashflow' => [
                'title' => 'Nakit Akis Raporu',
                'data' => [$this->getCashFlowReport($startDate, $endDate, $branchId, $userBranchIds)],
            ],
            'meal_cost' => [
                'title' => 'Yemek Maliyet Raporu',
                'data' => $this->getMealCostReport($startDate, $endDate, $branchId, $userBranchIds),
            ],
            default => ['title' => 'Rapor', 'data' => []],
        };
    }
}
