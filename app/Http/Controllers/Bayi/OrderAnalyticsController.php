<?php

namespace App\Http\Controllers\Bayi;

use App\Http\Controllers\Controller;
use App\Services\OrderAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OrderAnalyticsController extends Controller
{
    private OrderAnalyticsService $analyticsService;

    public function __construct(OrderAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Ana analitik dashboard
     */
    public function index(Request $request)
    {
        $period = $request->get('period', 'week');
        $branchId = $request->get('branch_id');

        [$startDate, $endDate] = $this->getDateRange($period);

        $overviewStats = $this->analyticsService->getOverviewStats($startDate, $endDate, $branchId);
        $dailyTrend = $this->analyticsService->getDailyTrend($startDate, $endDate, $branchId);
        $hourlyDistribution = $this->analyticsService->getHourlyDistribution($startDate, $endDate, $branchId);
        $paymentMethods = $this->analyticsService->getPaymentMethodDistribution($startDate, $endDate, $branchId);
        $statusDistribution = $this->analyticsService->getStatusDistribution($startDate, $endDate, $branchId);
        $topZones = $this->analyticsService->getTopZones($startDate, $endDate, 10, $branchId);
        $courierPerformance = $this->analyticsService->getCourierPerformance($startDate, $endDate, 10, $branchId);
        $realTimeStats = $this->analyticsService->getRealTimeStats($branchId);

        $branches = \App\Models\Branch::all();

        return view('bayi.analytics.index', compact(
            'overviewStats',
            'dailyTrend',
            'hourlyDistribution',
            'paymentMethods',
            'statusDistribution',
            'topZones',
            'courierPerformance',
            'realTimeStats',
            'branches',
            'period',
            'branchId',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Haftalik karsilastirma sayfasi
     */
    public function weeklyComparison(Request $request)
    {
        $branchId = $request->get('branch_id');
        $comparison = $this->analyticsService->getWeeklyComparison($branchId);
        $branches = \App\Models\Branch::all();

        return view('bayi.analytics.weekly', compact('comparison', 'branches', 'branchId'));
    }

    /**
     * Sube karsilastirma sayfasi
     */
    public function branchComparison(Request $request)
    {
        $period = $request->get('period', 'month');
        [$startDate, $endDate] = $this->getDateRange($period);

        $branchComparison = $this->analyticsService->getBranchComparison($startDate, $endDate);

        return view('bayi.analytics.branches', compact('branchComparison', 'period', 'startDate', 'endDate'));
    }

    /**
     * Heatmap sayfasi
     */
    public function heatmap(Request $request)
    {
        $period = $request->get('period', 'month');
        $branchId = $request->get('branch_id');
        [$startDate, $endDate] = $this->getDateRange($period);

        $heatmapData = $this->analyticsService->getOrderHeatmap($startDate, $endDate, $branchId);
        $branches = \App\Models\Branch::all();

        return view('bayi.analytics.heatmap', compact('heatmapData', 'branches', 'period', 'branchId', 'startDate', 'endDate'));
    }

    /**
     * API: Gercek zamanli istatistikler
     */
    public function realTimeApi(Request $request): JsonResponse
    {
        $branchId = $request->get('branch_id');
        $stats = $this->analyticsService->getRealTimeStats($branchId);

        return response()->json([
            'success' => true,
            'data' => $stats,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * API: Saatlik dagilim
     */
    public function hourlyApi(Request $request): JsonResponse
    {
        $period = $request->get('period', 'week');
        $branchId = $request->get('branch_id');
        [$startDate, $endDate] = $this->getDateRange($period);

        $data = $this->analyticsService->getHourlyDistribution($startDate, $endDate, $branchId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * API: Gunluk trend
     */
    public function dailyTrendApi(Request $request): JsonResponse
    {
        $period = $request->get('period', 'week');
        $branchId = $request->get('branch_id');
        [$startDate, $endDate] = $this->getDateRange($period);

        $data = $this->analyticsService->getDailyTrend($startDate, $endDate, $branchId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * API: Kurye performans
     */
    public function courierPerformanceApi(Request $request): JsonResponse
    {
        $period = $request->get('period', 'week');
        $branchId = $request->get('branch_id');
        $limit = $request->get('limit', 10);
        [$startDate, $endDate] = $this->getDateRange($period);

        $data = $this->analyticsService->getCourierPerformance($startDate, $endDate, $limit, $branchId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * API: Heatmap verisi
     */
    public function heatmapApi(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month');
        $branchId = $request->get('branch_id');
        [$startDate, $endDate] = $this->getDateRange($period);

        $data = $this->analyticsService->getOrderHeatmap($startDate, $endDate, $branchId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Tarih araligini hesapla
     */
    private function getDateRange(string $period): array
    {
        return match($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'last_week' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfWeek(), now()->endOfWeek()],
        };
    }
}
