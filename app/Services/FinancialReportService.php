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
     * Genel finansal özet
     */
    public function getFinancialSummary(Carbon $startDate, Carbon $endDate, ?int $branchId = null): array
    {
        $query = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'delivered');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->get();

        $totalRevenue = $orders->sum('total');
        $totalDeliveryFees = $orders->sum('delivery_fee');
        $totalSubtotal = $orders->sum('subtotal');
        $orderCount = $orders->count();

        // Ödeme yöntemine göre dağılım
        $paymentBreakdown = $orders->groupBy('payment_method')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('total'),
            ];
        });

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
    public function getDailyRevenueReport(Carbon $startDate, Carbon $endDate, ?int $branchId = null): Collection
    {
        $query = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'delivered');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->get();

        return $orders->groupBy(function ($order) {
            return $order->created_at->format('Y-m-d');
        })->map(function ($dayOrders, $date) {
            return [
                'date' => $date,
                'order_count' => $dayOrders->count(),
                'total_revenue' => round($dayOrders->sum('total'), 2),
                'delivery_fees' => round($dayOrders->sum('delivery_fee'), 2),
                'cash' => round($dayOrders->where('payment_method', 'cash')->sum('total'), 2),
                'card' => round($dayOrders->where('payment_method', 'card')->sum('total'), 2),
                'online' => round($dayOrders->where('payment_method', 'online')->sum('total'), 2),
            ];
        })->sortKeys()->values();
    }

    /**
     * Kurye performans ve kazanç raporu
     */
    public function getCourierEarningsReport(Carbon $startDate, Carbon $endDate, ?int $branchId = null): Collection
    {
        $query = Order::whereBetween('delivered_at', [$startDate, $endDate])
            ->where('status', 'delivered')
            ->whereNotNull('courier_id')
            ->with('courier');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

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

            // Toplanan nakit
            $cashCollected = $courierOrders->where('payment_method', 'cash')->sum('total');

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
    public function getBranchPerformanceReport(Carbon $startDate, Carbon $endDate): Collection
    {
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', Order::STATUS_DELIVERED)
            ->with('branch')
            ->get();

        return $orders->groupBy('branch_id')->map(function ($branchOrders) use ($startDate, $endDate) {
            $branch = $branchOrders->first()->branch;

            return [
                'branch_id' => $branch?->id,
                'branch_name' => $branch?->name ?? 'Ana Şube',
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
    public function getHourlyDistribution(Carbon $startDate, Carbon $endDate, ?int $branchId = null): array
    {
        $query = Order::whereBetween('created_at', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

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
    public function getWeeklyComparison(?int $branchId = null): array
    {
        $thisWeekStart = now()->startOfWeek();
        $thisWeekEnd = now()->endOfWeek();
        $lastWeekStart = now()->subWeek()->startOfWeek();
        $lastWeekEnd = now()->subWeek()->endOfWeek();

        $thisWeek = $this->getFinancialSummary($thisWeekStart, $thisWeekEnd, $branchId);
        $lastWeek = $this->getFinancialSummary($lastWeekStart, $lastWeekEnd, $branchId);

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
    public function getMonthlyComparison(?int $branchId = null): array
    {
        $thisMonthStart = now()->startOfMonth();
        $thisMonthEnd = now()->endOfMonth();
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $thisMonth = $this->getFinancialSummary($thisMonthStart, $thisMonthEnd, $branchId);
        $lastMonth = $this->getFinancialSummary($lastMonthStart, $lastMonthEnd, $branchId);

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
    public function getTopProducts(Carbon $startDate, Carbon $endDate, int $limit = 10, ?int $branchId = null): Collection
    {
        $query = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'delivered')
            ->with('items.product');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->get();

        $productSales = collect();

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $productId = $item->product_id ?? $item->id;
                $productName = $item->product?->name ?? $item->name ?? 'Bilinmeyen Ürün';

                if (!$productSales->has($productId)) {
                    $productSales[$productId] = [
                        'product_id' => $productId,
                        'product_name' => $productName,
                        'quantity' => 0,
                        'revenue' => 0,
                    ];
                }

                $productSales[$productId]['quantity'] += $item->quantity ?? 1;
                $productSales[$productId]['revenue'] += ($item->price ?? 0) * ($item->quantity ?? 1);
            }
        }

        return $productSales->sortByDesc('quantity')->take($limit)->values();
    }

    /**
     * Nakit akış raporu
     */
    public function getCashFlowReport(Carbon $startDate, Carbon $endDate, ?int $branchId = null): array
    {
        $query = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'delivered');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->get();

        // Nakit girişler
        $cashIncome = $orders->where('payment_method', 'cash')->sum('total');
        $cardIncome = $orders->where('payment_method', 'card')->sum('total');
        $onlineIncome = $orders->where('payment_method', 'online')->sum('total');

        // Kurye ödemeleri (ayarlardan veya varsayılan %70)
        $courierPaymentRate = config('services.courier.payment_rate', 0.7);
        $courierPayments = $orders->sum('delivery_fee') * $courierPaymentRate;

        return [
            'income' => [
                'cash' => round($cashIncome, 2),
                'card' => round($cardIncome, 2),
                'online' => round($onlineIncome, 2),
                'total' => round($cashIncome + $cardIncome + $onlineIncome, 2),
            ],
            'expenses' => [
                'courier_payments' => round($courierPayments, 2),
            ],
            'net_cash_flow' => round(($cashIncome + $cardIncome + $onlineIncome) - $courierPayments, 2),
        ];
    }

    /**
     * Rapor dışa aktarma için veri hazırla
     */
    public function prepareExportData(string $reportType, Carbon $startDate, Carbon $endDate, ?int $branchId = null): array
    {
        return match ($reportType) {
            'summary' => [
                'title' => 'Finansal Özet Raporu',
                'data' => $this->getFinancialSummary($startDate, $endDate, $branchId),
            ],
            'daily' => [
                'title' => 'Günlük Gelir Raporu',
                'data' => $this->getDailyRevenueReport($startDate, $endDate, $branchId)->toArray(),
            ],
            'courier' => [
                'title' => 'Kurye Kazanç Raporu',
                'data' => $this->getCourierEarningsReport($startDate, $endDate, $branchId)->toArray(),
            ],
            'branch' => [
                'title' => 'Şube Performans Raporu',
                'data' => $this->getBranchPerformanceReport($startDate, $endDate)->toArray(),
            ],
            default => ['title' => 'Rapor', 'data' => []],
        };
    }
}
