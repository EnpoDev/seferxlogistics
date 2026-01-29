<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Courier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PoolAnalyticsService
{
    /**
     * Get comprehensive pool analytics for a given period
     */
    public function getAnalytics(Carbon $startDate, Carbon $endDate, ?int $branchId = null): array
    {
        $query = Order::whereNotNull('pool_entered_at')
            ->whereBetween('pool_entered_at', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->get();

        // Calculate metrics
        $totalPoolOrders = $orders->count();
        $assignedOrders = $orders->whereNotNull('courier_id');
        $timedOutOrders = $orders->filter(fn($o) => $o->poolWaitingMinutes() >= 5);

        // Calculate average wait time
        $avgWaitTime = $orders->avg(function ($order) {
            if ($order->courier_id && $order->pool_entered_at) {
                // For assigned orders, calculate time until assignment
                $assignedAt = $order->updated_at;
                return $order->pool_entered_at->diffInMinutes($assignedAt);
            }
            return $order->poolWaitingMinutes() ?? 0;
        });

        // Peak hours analysis
        $peakHours = $this->analyzePeakHours($startDate, $endDate, $branchId);

        // Courier performance in pool
        $courierPerformance = $this->getCourierPoolPerformance($startDate, $endDate, $branchId);

        // Daily breakdown
        $dailyBreakdown = $this->getDailyBreakdown($startDate, $endDate, $branchId);

        return [
            'summary' => [
                'total_pool_orders' => $totalPoolOrders,
                'assigned_count' => $assignedOrders->count(),
                'pending_count' => $orders->whereNull('courier_id')->count(),
                'timeout_count' => $timedOutOrders->count(),
                'assignment_rate' => $totalPoolOrders > 0
                    ? round(($assignedOrders->count() / $totalPoolOrders) * 100, 1)
                    : 0,
                'timeout_rate' => $totalPoolOrders > 0
                    ? round(($timedOutOrders->count() / $totalPoolOrders) * 100, 1)
                    : 0,
                'avg_wait_time_minutes' => round($avgWaitTime ?? 0, 1),
            ],
            'peak_hours' => $peakHours,
            'courier_performance' => $courierPerformance,
            'daily_breakdown' => $dailyBreakdown,
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ];
    }

    /**
     * Analyze peak hours for pool orders (SQLite/MySQL compatible)
     */
    private function analyzePeakHours(Carbon $startDate, Carbon $endDate, ?int $branchId = null): array
    {
        $query = Order::whereNotNull('pool_entered_at')
            ->whereBetween('pool_entered_at', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->get();

        // Group by hour using PHP for database compatibility
        $hourlyData = $orders->groupBy(function ($order) {
            return $order->pool_entered_at->format('H');
        })->map(fn($group) => $group->count())
          ->sortDesc();

        $peakHours = $hourlyData->take(3)->map(fn($count, $hour) => [
            'hour' => sprintf('%s:00', $hour),
            'count' => $count,
        ])->values();

        return [
            'peak_times' => $peakHours,
            'hourly_distribution' => $hourlyData->toArray(),
        ];
    }

    /**
     * Get courier performance metrics for pool orders
     */
    private function getCourierPoolPerformance(Carbon $startDate, Carbon $endDate, ?int $branchId = null): array
    {
        $query = Order::whereNotNull('pool_entered_at')
            ->whereNotNull('courier_id')
            ->whereBetween('pool_entered_at', [$startDate, $endDate])
            ->with('courier');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->get();

        $courierStats = $orders->groupBy('courier_id')->map(function ($courierOrders) {
            $courier = $courierOrders->first()->courier;

            return [
                'courier_id' => $courier?->id,
                'courier_name' => $courier?->name ?? 'Bilinmeyen',
                'accepted_count' => $courierOrders->count(),
                'avg_acceptance_time' => round($courierOrders->avg(function ($order) {
                    if ($order->pool_entered_at && $order->updated_at) {
                        return $order->pool_entered_at->diffInMinutes($order->updated_at);
                    }
                    return 0;
                }), 1),
                'delivered_count' => $courierOrders->where('status', 'delivered')->count(),
            ];
        })->sortByDesc('accepted_count')->take(10)->values();

        return $courierStats->toArray();
    }

    /**
     * Get daily breakdown of pool metrics (SQLite/MySQL compatible)
     */
    private function getDailyBreakdown(Carbon $startDate, Carbon $endDate, ?int $branchId = null): array
    {
        $query = Order::whereNotNull('pool_entered_at')
            ->whereBetween('pool_entered_at', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->get();

        // Group by date using PHP for database compatibility
        return $orders->groupBy(function ($order) {
            return $order->pool_entered_at->format('Y-m-d');
        })->map(function ($dayOrders, $date) {
            $total = $dayOrders->count();
            $assigned = $dayOrders->whereNotNull('courier_id')->count();

            return [
                'date' => $date,
                'total' => $total,
                'assigned' => $assigned,
                'pending' => $total - $assigned,
                'assignment_rate' => $total > 0
                    ? round(($assigned / $total) * 100, 1)
                    : 0,
            ];
        })->sortKeys()->values()->toArray();
    }

    /**
     * Get real-time pool status
     */
    public function getRealTimeStatus(?int $branchId = null): array
    {
        $poolService = new PoolService();
        $poolOrders = $poolService->getPoolOrders($branchId);

        // Group by waiting time ranges
        $byWaitTime = [
            '0-2' => $poolOrders->filter(fn($o) => ($o->poolWaitingMinutes() ?? 0) < 2)->count(),
            '2-5' => $poolOrders->filter(fn($o) => ($o->poolWaitingMinutes() ?? 0) >= 2 && ($o->poolWaitingMinutes() ?? 0) < 5)->count(),
            '5-10' => $poolOrders->filter(fn($o) => ($o->poolWaitingMinutes() ?? 0) >= 5 && ($o->poolWaitingMinutes() ?? 0) < 10)->count(),
            '10+' => $poolOrders->filter(fn($o) => ($o->poolWaitingMinutes() ?? 0) >= 10)->count(),
        ];

        // Available couriers
        $availableCouriers = Courier::where('status', Courier::STATUS_AVAILABLE)
            ->orWhere(function ($q) {
                $q->where('status', Courier::STATUS_BUSY)
                  ->where('active_orders_count', '<', Courier::MAX_ACTIVE_ORDERS);
            })
            ->get()
            ->filter(fn($c) => $c->isOnShift());

        return [
            'current_pool_count' => $poolOrders->count(),
            'wait_time_distribution' => $byWaitTime,
            'available_couriers' => $availableCouriers->count(),
            'oldest_order_minutes' => $poolOrders->max(fn($o) => $o->poolWaitingMinutes() ?? 0),
            'avg_wait_time' => $poolOrders->isNotEmpty()
                ? round($poolOrders->avg(fn($o) => $o->poolWaitingMinutes() ?? 0), 1)
                : 0,
            'orders_per_courier_ratio' => $availableCouriers->count() > 0
                ? round($poolOrders->count() / $availableCouriers->count(), 2)
                : $poolOrders->count(),
        ];
    }

    /**
     * Get pool efficiency score (0-100)
     */
    public function getEfficiencyScore(?int $branchId = null): array
    {
        $today = now()->startOfDay();
        $todayOrders = Order::whereNotNull('pool_entered_at')
            ->whereDate('pool_entered_at', $today)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();

        if ($todayOrders->isEmpty()) {
            return [
                'score' => 100,
                'grade' => 'A',
                'factors' => [],
            ];
        }

        $factors = [];
        $score = 100;

        // Factor 1: Assignment rate (30 points)
        $assignmentRate = $todayOrders->whereNotNull('courier_id')->count() / $todayOrders->count();
        $assignmentScore = $assignmentRate * 30;
        $factors['assignment_rate'] = [
            'value' => round($assignmentRate * 100, 1),
            'score' => round($assignmentScore, 1),
            'max' => 30,
        ];

        // Factor 2: Average wait time (40 points, lower is better)
        $avgWait = $todayOrders->avg(fn($o) => $o->poolWaitingMinutes() ?? 0);
        $waitScore = max(0, 40 - ($avgWait * 4)); // -4 points per minute
        $factors['avg_wait_time'] = [
            'value' => round($avgWait, 1),
            'score' => round($waitScore, 1),
            'max' => 40,
        ];

        // Factor 3: Timeout rate (30 points, lower is better)
        $timeoutRate = $todayOrders->filter(fn($o) => ($o->poolWaitingMinutes() ?? 0) >= 5)->count() / $todayOrders->count();
        $timeoutScore = (1 - $timeoutRate) * 30;
        $factors['timeout_rate'] = [
            'value' => round($timeoutRate * 100, 1),
            'score' => round($timeoutScore, 1),
            'max' => 30,
        ];

        $totalScore = $assignmentScore + $waitScore + $timeoutScore;

        // Determine grade
        $grade = match (true) {
            $totalScore >= 90 => 'A',
            $totalScore >= 75 => 'B',
            $totalScore >= 60 => 'C',
            $totalScore >= 40 => 'D',
            default => 'F',
        };

        return [
            'score' => round($totalScore, 1),
            'grade' => $grade,
            'factors' => $factors,
        ];
    }
}
