<?php

namespace App\Http\Controllers\Bayi;

use App\Http\Controllers\Controller;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FinansController extends Controller
{
    public function __construct(
        private FinancialReportService $reportService
    ) {}

    /**
     * Finansal dashboard
     */
    public function index(Request $request)
    {
        $period = $request->get('period', 'week');
        $branchId = $request->get('branch_id');

        [$startDate, $endDate] = $this->getPeriodDates($period);

        $summary = $this->reportService->getFinancialSummary($startDate, $endDate, $branchId);
        $dailyRevenue = $this->reportService->getDailyRevenueReport($startDate, $endDate, $branchId);
        $hourlyDistribution = $this->reportService->getHourlyDistribution($startDate, $endDate, $branchId);
        $weeklyComparison = $this->reportService->getWeeklyComparison($branchId);
        $monthlyComparison = $this->reportService->getMonthlyComparison($branchId);

        return view('bayi.finans.index', compact(
            'summary',
            'dailyRevenue',
            'hourlyDistribution',
            'weeklyComparison',
            'monthlyComparison',
            'period',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Kurye kazanç raporu
     */
    public function kuryeKazanc(Request $request)
    {
        $period = $request->get('period', 'month');
        [$startDate, $endDate] = $this->getPeriodDates($period);

        $courierEarnings = $this->reportService->getCourierEarningsReport($startDate, $endDate);

        return view('bayi.finans.kurye-kazanc', compact('courierEarnings', 'period', 'startDate', 'endDate'));
    }

    /**
     * Şube performans raporu
     */
    public function subePerformans(Request $request)
    {
        $period = $request->get('period', 'month');
        [$startDate, $endDate] = $this->getPeriodDates($period);

        $branchPerformance = $this->reportService->getBranchPerformanceReport($startDate, $endDate);

        return view('bayi.finans.sube-performans', compact('branchPerformance', 'period', 'startDate', 'endDate'));
    }

    /**
     * Nakit akış raporu
     */
    public function nakitAkis(Request $request)
    {
        $period = $request->get('period', 'month');
        $branchId = $request->get('branch_id');
        [$startDate, $endDate] = $this->getPeriodDates($period);

        $cashFlow = $this->reportService->getCashFlowReport($startDate, $endDate, $branchId);
        $dailyRevenue = $this->reportService->getDailyRevenueReport($startDate, $endDate, $branchId);

        return view('bayi.finans.nakit-akis', compact('cashFlow', 'dailyRevenue', 'period', 'startDate', 'endDate'));
    }

    /**
     * API: Rapor verileri
     */
    public function apiData(Request $request)
    {
        $reportType = $request->get('type', 'summary');
        $period = $request->get('period', 'week');
        $branchId = $request->get('branch_id');

        [$startDate, $endDate] = $this->getPeriodDates($period);

        $data = match ($reportType) {
            'summary' => $this->reportService->getFinancialSummary($startDate, $endDate, $branchId),
            'daily' => $this->reportService->getDailyRevenueReport($startDate, $endDate, $branchId),
            'courier' => $this->reportService->getCourierEarningsReport($startDate, $endDate, $branchId),
            'branch' => $this->reportService->getBranchPerformanceReport($startDate, $endDate),
            'hourly' => $this->reportService->getHourlyDistribution($startDate, $endDate, $branchId),
            'weekly' => $this->reportService->getWeeklyComparison($branchId),
            'monthly' => $this->reportService->getMonthlyComparison($branchId),
            'cashflow' => $this->reportService->getCashFlowReport($startDate, $endDate, $branchId),
            default => [],
        };

        return response()->json([
            'success' => true,
            'data' => $data,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Rapor dışa aktarma
     */
    public function export(Request $request)
    {
        $reportType = $request->get('type', 'summary');
        $period = $request->get('period', 'month');
        $format = $request->get('format', 'csv');
        $branchId = $request->get('branch_id');

        [$startDate, $endDate] = $this->getPeriodDates($period);

        $exportData = $this->reportService->prepareExportData($reportType, $startDate, $endDate, $branchId);

        if ($format === 'csv') {
            return $this->exportCsv($exportData);
        }

        return response()->json($exportData);
    }

    /**
     * CSV olarak dışa aktar
     */
    private function exportCsv(array $exportData)
    {
        $filename = 'rapor_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($exportData) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            // Başlık
            fputcsv($file, [$exportData['title']]);
            fputcsv($file, []);

            // Veri
            $data = $exportData['data'];
            if (is_array($data) && !empty($data)) {
                // İlk satırdan başlıkları al
                $firstRow = is_array(reset($data)) ? reset($data) : $data;
                if (is_array($firstRow)) {
                    fputcsv($file, array_keys($firstRow));
                    foreach ($data as $row) {
                        if (is_array($row)) {
                            fputcsv($file, array_values($row));
                        }
                    }
                } else {
                    foreach ($data as $key => $value) {
                        fputcsv($file, [$key, is_array($value) ? json_encode($value) : $value]);
                    }
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Periyoda göre tarih aralığı
     */
    private function getPeriodDates(string $period): array
    {
        return match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'quarter' => [now()->startOfQuarter(), now()->endOfQuarter()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->subDays(7), now()],
        };
    }
}
