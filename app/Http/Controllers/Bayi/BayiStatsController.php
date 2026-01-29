<?php

namespace App\Http\Controllers\Bayi;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\Order;
use Illuminate\Http\Request;

class BayiStatsController extends Controller
{
    public function kullaniciYonetimi()
    {
        $users = \App\Models\User::orderBy('name')->paginate(20);
        return view('bayi.kullanici-yonetimi', compact('users'));
    }

    public function istatistik()
    {
        // Calculate real average delivery time
        $deliveredOrders = Order::whereNotNull('delivered_at')
            ->whereNotNull('created_at')
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->get();

        $avgDeliveryTime = 0;
        if ($deliveredOrders->count() > 0) {
            $totalMinutes = $deliveredOrders->sum(function ($order) {
                return $order->created_at->diffInMinutes($order->delivered_at);
            });
            $avgDeliveryTime = round($totalMinutes / $deliveredOrders->count());
        }

        // Calculate completion rate
        $totalOrders = Order::whereDate('created_at', '>=', now()->subDays(30))->count();
        $completedOrders = Order::whereDate('created_at', '>=', now()->subDays(30))
            ->where('status', 'delivered')
            ->count();
        $completionRate = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 1) : 0;

        $stats = [
            'today_orders' => Order::whereDate('created_at', today())->count(),
            'today_revenue' => Order::whereDate('created_at', today())
                ->where('status', 'delivered')->sum('total'),
            'active_couriers' => Courier::whereIn('status', ['available', 'busy'])->count(),
            'avg_delivery_time' => $avgDeliveryTime ?: 0,
            'completion_rate' => $completionRate,
            'pending_orders' => Order::where('status', 'pending')->count(),
            'on_delivery_orders' => Order::where('status', 'on_delivery')->count(),
        ];

        return view('bayi.istatistik', compact('stats'));
    }

    public function gelismisIstatistik(Request $request)
    {
        // Date range filtering
        $period = $request->get('period', '7days');

        [$startDate, $endDate] = $this->getDateRange($period);

        // Initialize statistics service
        $statsService = new \App\Services\AdvancedStatisticsService($startDate, $endDate);

        // Get all statistics
        $stats = $statsService->getAllStatistics();
        $topPerformers = $statsService->getTopPerformers();

        // Merge top performers
        $stats['top_couriers'] = $topPerformers['top_couriers'];
        $stats['top_branches'] = $topPerformers['top_branches'];

        // Add period info
        $stats['period'] = $period;
        $stats['start_date'] = $startDate->format('d.m.Y');
        $stats['end_date'] = $endDate->format('d.m.Y');

        return view('bayi.gelismis-istatistik', compact('stats'));
    }

    private function getDateRange(string $period): array
    {
        $endDate = now();

        $startDate = match($period) {
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            'this_month' => now()->startOfMonth(),
            'last_month' => now()->subMonth()->startOfMonth(),
            default => now()->subDays(7),
        };

        if ($period === 'last_month') {
            $endDate = now()->subMonth()->endOfMonth();
        }

        return [$startDate, $endDate];
    }
}
