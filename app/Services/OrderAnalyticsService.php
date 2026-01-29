<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Branch;
use App\Models\Courier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderAnalyticsService
{
    /**
     * Genel siparis istatistikleri
     */
    public function getOverviewStats(Carbon $startDate, Carbon $endDate, ?int $branchId = null): array
    {
        $query = Order::whereBetween('created_at', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $totalOrders = $query->count();
        $deliveredOrders = (clone $query)->where('status', 'delivered')->count();
        $cancelledOrders = (clone $query)->where('status', 'cancelled')->count();
        $totalRevenue = (clone $query)->where('status', 'delivered')->sum('total');
        $avgOrderValue = $deliveredOrders > 0 ? $totalRevenue / $deliveredOrders : 0;

        // Ortalama teslimat suresi (dakika)
        $avgDeliveryTime = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'delivered')
            ->whereNotNull('delivered_at')
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get()
            ->avg(function ($order) {
                if (!$order->delivered_at || !$order->created_at) {
                    return null;
                }
                return Carbon::parse($order->created_at)->diffInMinutes(Carbon::parse($order->delivered_at));
            });

        // Onceki donem karsilastirmasi
        $daysDiff = $startDate->diffInDays($endDate);
        $previousStart = (clone $startDate)->subDays($daysDiff);
        $previousEnd = (clone $endDate)->subDays($daysDiff);

        $previousQuery = Order::whereBetween('created_at', [$previousStart, $previousEnd]);
        if ($branchId) {
            $previousQuery->where('branch_id', $branchId);
        }

        $previousTotal = $previousQuery->count();
        $previousRevenue = (clone $previousQuery)->where('status', 'delivered')->sum('total');

        $orderGrowth = $previousTotal > 0 ? round((($totalOrders - $previousTotal) / $previousTotal) * 100, 1) : 0;
        $revenueGrowth = $previousRevenue > 0 ? round((($totalRevenue - $previousRevenue) / $previousRevenue) * 100, 1) : 0;

        return [
            'total_orders' => $totalOrders,
            'delivered_orders' => $deliveredOrders,
            'cancelled_orders' => $cancelledOrders,
            'total_revenue' => round($totalRevenue, 2),
            'avg_order_value' => round($avgOrderValue, 2),
            'avg_delivery_time' => round($avgDeliveryTime ?? 0),
            'delivery_rate' => $totalOrders > 0 ? round(($deliveredOrders / $totalOrders) * 100, 1) : 0,
            'cancel_rate' => $totalOrders > 0 ? round(($cancelledOrders / $totalOrders) * 100, 1) : 0,
            'order_growth' => $orderGrowth,
            'revenue_growth' => $revenueGrowth,
        ];
    }

    /**
     * Saatlik siparis dagilimi
     */
    public function getHourlyDistribution(Carbon $startDate, Carbon $endDate, ?int $branchId = null): array
    {
        $query = Order::whereBetween('created_at', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->get();

        $hourly = array_fill(0, 24, ['orders' => 0, 'revenue' => 0]);

        foreach ($orders as $order) {
            $hour = (int) Carbon::parse($order->created_at)->format('H');
            $hourly[$hour]['orders']++;
            if ($order->status === 'delivered') {
                $hourly[$hour]['revenue'] += $order->total;
            }
        }

        $result = [];
        foreach ($hourly as $hour => $data) {
            $result[] = [
                'hour' => sprintf('%02d:00', $hour),
                'orders' => $data['orders'],
                'revenue' => round($data['revenue'], 2),
            ];
        }

        return $result;
    }

    /**
     * Gunluk siparis trendi
     */
    public function getDailyTrend(Carbon $startDate, Carbon $endDate, ?int $branchId = null): array
    {
        $query = Order::whereBetween('created_at', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->get();

        $daily = [];
        $current = clone $startDate;

        while ($current->lte($endDate)) {
            $dateKey = $current->format('Y-m-d');
            $daily[$dateKey] = ['orders' => 0, 'revenue' => 0, 'delivered' => 0, 'cancelled' => 0];
            $current->addDay();
        }

        foreach ($orders as $order) {
            $dateKey = Carbon::parse($order->created_at)->format('Y-m-d');
            if (isset($daily[$dateKey])) {
                $daily[$dateKey]['orders']++;
                if ($order->status === 'delivered') {
                    $daily[$dateKey]['delivered']++;
                    $daily[$dateKey]['revenue'] += $order->total;
                } elseif ($order->status === 'cancelled') {
                    $daily[$dateKey]['cancelled']++;
                }
            }
        }

        $result = [];
        foreach ($daily as $date => $data) {
            $result[] = [
                'date' => $date,
                'display_date' => Carbon::parse($date)->format('d.m'),
                'orders' => $data['orders'],
                'revenue' => round($data['revenue'], 2),
                'delivered' => $data['delivered'],
                'cancelled' => $data['cancelled'],
            ];
        }

        return $result;
    }

    /**
     * Odeme yontemi dagilimi
     */
    public function getPaymentMethodDistribution(Carbon $startDate, Carbon $endDate, ?int $branchId = null): array
    {
        $query = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'delivered');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->get();

        $methods = [
            'cash' => ['count' => 0, 'amount' => 0, 'label' => 'Nakit'],
            'credit_card' => ['count' => 0, 'amount' => 0, 'label' => 'Kredi Karti'],
            'online' => ['count' => 0, 'amount' => 0, 'label' => 'Online'],
        ];

        foreach ($orders as $order) {
            $method = $order->payment_method ?? 'cash';
            if (!isset($methods[$method])) {
                $method = 'cash';
            }
            $methods[$method]['count']++;
            $methods[$method]['amount'] += $order->total;
        }

        $total = array_sum(array_column($methods, 'count'));

        $result = [];
        foreach ($methods as $key => $data) {
            $result[] = [
                'method' => $key,
                'label' => $data['label'],
                'count' => $data['count'],
                'amount' => round($data['amount'], 2),
                'percentage' => $total > 0 ? round(($data['count'] / $total) * 100, 1) : 0,
            ];
        }

        return $result;
    }

    /**
     * Siparis durum dagilimi
     */
    public function getStatusDistribution(Carbon $startDate, Carbon $endDate, ?int $branchId = null): array
    {
        $query = Order::whereBetween('created_at', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->get();

        $statuses = [
            'pending' => ['count' => 0, 'label' => 'Beklemede', 'color' => '#F59E0B'],
            'preparing' => ['count' => 0, 'label' => 'Hazirlaniyor', 'color' => '#3B82F6'],
            'ready' => ['count' => 0, 'label' => 'Hazir', 'color' => '#8B5CF6'],
            'on_way' => ['count' => 0, 'label' => 'Yolda', 'color' => '#10B981'],
            'delivered' => ['count' => 0, 'label' => 'Teslim Edildi', 'color' => '#22C55E'],
            'cancelled' => ['count' => 0, 'label' => 'Iptal', 'color' => '#EF4444'],
        ];

        foreach ($orders as $order) {
            $status = $order->status ?? 'pending';
            if (isset($statuses[$status])) {
                $statuses[$status]['count']++;
            }
        }

        $total = array_sum(array_column($statuses, 'count'));

        $result = [];
        foreach ($statuses as $key => $data) {
            $result[] = [
                'status' => $key,
                'label' => $data['label'],
                'color' => $data['color'],
                'count' => $data['count'],
                'percentage' => $total > 0 ? round(($data['count'] / $total) * 100, 1) : 0,
            ];
        }

        return $result;
    }

    /**
     * En cok siparis alinan bolgeler
     */
    public function getTopZones(Carbon $startDate, Carbon $endDate, int $limit = 10, ?int $branchId = null): array
    {
        $query = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('customer_address');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->get();

        $zones = [];
        foreach ($orders as $order) {
            // Adres'ten ilce/mahalle cikarma
            $address = $order->customer_address ?? '';
            $parts = explode(',', $address);
            $zone = trim($parts[0] ?? 'Bilinmeyen');

            if (!isset($zones[$zone])) {
                $zones[$zone] = ['count' => 0, 'revenue' => 0];
            }
            $zones[$zone]['count']++;
            if ($order->status === 'delivered') {
                $zones[$zone]['revenue'] += $order->total;
            }
        }

        uasort($zones, fn($a, $b) => $b['count'] <=> $a['count']);
        $zones = array_slice($zones, 0, $limit, true);

        $result = [];
        foreach ($zones as $zone => $data) {
            $result[] = [
                'zone' => $zone,
                'orders' => $data['count'],
                'revenue' => round($data['revenue'], 2),
            ];
        }

        return $result;
    }

    /**
     * Kurye performans siralamasi
     */
    public function getCourierPerformance(Carbon $startDate, Carbon $endDate, int $limit = 10, ?int $branchId = null): array
    {
        $query = Order::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'delivered')
            ->whereNotNull('courier_id');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->with('courier')->get();

        $couriers = [];
        foreach ($orders as $order) {
            $courierId = $order->courier_id;
            if (!isset($couriers[$courierId])) {
                $couriers[$courierId] = [
                    'id' => $courierId,
                    'name' => $order->courier?->name ?? 'Bilinmeyen',
                    'deliveries' => 0,
                    'revenue' => 0,
                    'total_time' => 0,
                    'delivery_count_with_time' => 0,
                ];
            }

            $couriers[$courierId]['deliveries']++;
            $couriers[$courierId]['revenue'] += $order->total;

            if ($order->delivered_at && $order->on_way_at) {
                $deliveryTime = Carbon::parse($order->on_way_at)->diffInMinutes(Carbon::parse($order->delivered_at));
                $couriers[$courierId]['total_time'] += $deliveryTime;
                $couriers[$courierId]['delivery_count_with_time']++;
            }
        }

        // Ortalama teslimat suresi hesapla
        foreach ($couriers as &$courier) {
            $courier['avg_delivery_time'] = $courier['delivery_count_with_time'] > 0
                ? round($courier['total_time'] / $courier['delivery_count_with_time'])
                : 0;
            unset($courier['total_time'], $courier['delivery_count_with_time']);
        }

        uasort($couriers, fn($a, $b) => $b['deliveries'] <=> $a['deliveries']);
        $couriers = array_slice(array_values($couriers), 0, $limit);

        return $couriers;
    }

    /**
     * Sube performans karsilastirmasi
     */
    public function getBranchComparison(Carbon $startDate, Carbon $endDate): array
    {
        $branches = Branch::with(['orders' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])->get();

        $result = [];
        foreach ($branches as $branch) {
            $orders = $branch->orders;
            $totalOrders = $orders->count();
            $deliveredOrders = $orders->where('status', 'delivered')->count();
            $totalRevenue = $orders->where('status', 'delivered')->sum('total');

            $result[] = [
                'id' => $branch->id,
                'name' => $branch->name,
                'total_orders' => $totalOrders,
                'delivered_orders' => $deliveredOrders,
                'revenue' => round($totalRevenue, 2),
                'avg_order_value' => $deliveredOrders > 0 ? round($totalRevenue / $deliveredOrders, 2) : 0,
                'delivery_rate' => $totalOrders > 0 ? round(($deliveredOrders / $totalOrders) * 100, 1) : 0,
            ];
        }

        usort($result, fn($a, $b) => $b['revenue'] <=> $a['revenue']);

        return $result;
    }

    /**
     * Haftalik karsilastirma (bu hafta vs gecen hafta)
     */
    public function getWeeklyComparison(?int $branchId = null): array
    {
        $thisWeekStart = now()->startOfWeek();
        $thisWeekEnd = now()->endOfWeek();
        $lastWeekStart = now()->subWeek()->startOfWeek();
        $lastWeekEnd = now()->subWeek()->endOfWeek();

        $thisWeekStats = $this->getOverviewStats($thisWeekStart, $thisWeekEnd, $branchId);
        $lastWeekStats = $this->getOverviewStats($lastWeekStart, $lastWeekEnd, $branchId);

        return [
            'this_week' => [
                'label' => 'Bu Hafta',
                'start' => $thisWeekStart->format('d.m.Y'),
                'end' => $thisWeekEnd->format('d.m.Y'),
                'stats' => $thisWeekStats,
            ],
            'last_week' => [
                'label' => 'Gecen Hafta',
                'start' => $lastWeekStart->format('d.m.Y'),
                'end' => $lastWeekEnd->format('d.m.Y'),
                'stats' => $lastWeekStats,
            ],
            'comparison' => [
                'orders_diff' => $thisWeekStats['total_orders'] - $lastWeekStats['total_orders'],
                'revenue_diff' => round($thisWeekStats['total_revenue'] - $lastWeekStats['total_revenue'], 2),
                'delivery_rate_diff' => round($thisWeekStats['delivery_rate'] - $lastWeekStats['delivery_rate'], 1),
            ],
        ];
    }

    /**
     * Gercek zamanli siparis ozeti
     */
    public function getRealTimeStats(?int $branchId = null): array
    {
        $todayStart = now()->startOfDay();

        $query = Order::where('created_at', '>=', $todayStart);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->get();

        $pending = $orders->whereIn('status', ['pending', 'preparing', 'ready'])->count();
        $onWay = $orders->where('status', 'on_way')->count();
        $delivered = $orders->where('status', 'delivered')->count();
        $cancelled = $orders->where('status', 'cancelled')->count();

        $lastHourOrders = $orders->filter(function ($order) {
            return Carbon::parse($order->created_at)->gte(now()->subHour());
        })->count();

        return [
            'pending' => $pending,
            'on_way' => $onWay,
            'delivered' => $delivered,
            'cancelled' => $cancelled,
            'total_today' => $orders->count(),
            'last_hour' => $lastHourOrders,
            'revenue_today' => round($orders->where('status', 'delivered')->sum('total'), 2),
        ];
    }

    /**
     * Siparis heatmap verisi (gun/saat bazinda)
     */
    public function getOrderHeatmap(Carbon $startDate, Carbon $endDate, ?int $branchId = null): array
    {
        $query = Order::whereBetween('created_at', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->get();

        // 7 gun x 24 saat matrix
        $heatmap = [];
        $days = ['Pazartesi', 'Sali', 'Carsamba', 'Persembe', 'Cuma', 'Cumartesi', 'Pazar'];

        foreach ($days as $dayIndex => $day) {
            $heatmap[$dayIndex] = [];
            for ($hour = 0; $hour < 24; $hour++) {
                $heatmap[$dayIndex][$hour] = 0;
            }
        }

        foreach ($orders as $order) {
            $date = Carbon::parse($order->created_at);
            $dayOfWeek = $date->dayOfWeekIso - 1; // 0-6 (Pazartesi-Pazar)
            $hour = (int) $date->format('H');

            $heatmap[$dayOfWeek][$hour]++;
        }

        $result = [];
        foreach ($heatmap as $dayIndex => $hours) {
            foreach ($hours as $hour => $count) {
                $result[] = [
                    'day' => $dayIndex,
                    'day_name' => $days[$dayIndex],
                    'hour' => $hour,
                    'count' => $count,
                ];
            }
        }

        return $result;
    }
}
