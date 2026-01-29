<?php

namespace App\Http\Controllers\Bayi;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Services\ShiftManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ShiftController extends Controller
{
    private ShiftManagementService $shiftService;

    public function __construct(ShiftManagementService $shiftService)
    {
        $this->shiftService = $shiftService;
    }

    /**
     * Vardiya analitik dashboard
     */
    public function analytics(Request $request)
    {
        $period = $request->get('period', 'week');

        [$startDate, $endDate] = match($period) {
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            default => [now()->startOfWeek(), now()->endOfWeek()],
        };

        // Saatlik kurye dağılımı
        $hourlyDistribution = $this->shiftService->getActiveCouriersByHour();

        // Vardiya istatistikleri
        $shiftStats = $this->shiftService->getShiftStatistics($startDate, $endDate);

        // Vardiya şablonları
        $templates = $this->shiftService->getShiftTemplates();

        // Aktif kuryeler ve durumları
        $couriers = Courier::where('status', 'active')->get()->map(function ($courier) {
            $status = $this->shiftService->checkCourierShiftStatus($courier);
            return [
                'id' => $courier->id,
                'name' => $courier->name,
                'avatar' => $courier->getAvatarUrl(),
                'is_on_shift' => $status['is_on_shift'],
                'current_shift' => $status['current_shift'],
                'next_shift' => $status['next_shift'],
                'minutes_until_shift' => $status['minutes_until_shift'],
                'remaining_minutes' => $status['remaining_minutes'],
            ];
        });

        $onShiftCount = $couriers->where('is_on_shift', true)->count();
        $offShiftCount = $couriers->where('is_on_shift', false)->count();

        return view('bayi.vardiya.analytics', compact(
            'hourlyDistribution',
            'shiftStats',
            'templates',
            'couriers',
            'onShiftCount',
            'offShiftCount',
            'period',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Kurye vardiya durumu API
     */
    public function courierStatus(Courier $courier): JsonResponse
    {
        $status = $this->shiftService->checkCourierShiftStatus($courier);

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Kurye için optimal vardiya önerisi
     */
    public function suggest(Courier $courier): JsonResponse
    {
        $suggestion = $this->shiftService->suggestOptimalShifts($courier->id);

        return response()->json([
            'success' => true,
            'data' => $suggestion,
        ]);
    }

    /**
     * Şablon uygula
     */
    public function applyTemplate(Request $request, Courier $courier): JsonResponse
    {
        $validated = $request->validate([
            'template' => 'required|string',
            'days' => 'nullable|array',
            'days.*' => 'string',
        ]);

        $result = $this->shiftService->applyShiftTemplate(
            $courier,
            $validated['template'],
            $validated['days'] ?? null
        );

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Şablon bulunamadı.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Şablon başarıyla uygulandı.',
        ]);
    }

    /**
     * Vardiya çakışma kontrolü
     */
    public function checkConflicts(Request $request, Courier $courier): JsonResponse
    {
        $validated = $request->validate([
            'day' => 'required|string',
            'start' => 'required|string',
            'end' => 'required|string',
        ]);

        $conflicts = $this->shiftService->checkShiftConflicts(
            $courier,
            $validated['day'],
            $validated['start'],
            $validated['end']
        );

        return response()->json([
            'success' => true,
            'has_conflicts' => count($conflicts) > 0,
            'conflicts' => $conflicts,
        ]);
    }

    /**
     * Saatlik aktif kurye sayısı API
     */
    public function hourlyData(): JsonResponse
    {
        $data = $this->shiftService->getActiveCouriersByHour();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Vardiya istatistikleri API
     */
    public function statistics(Request $request): JsonResponse
    {
        $period = $request->get('period', 'week');
        $courierId = $request->get('courier_id');

        [$startDate, $endDate] = match($period) {
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            default => [now()->startOfWeek(), now()->endOfWeek()],
        };

        $stats = $this->shiftService->getShiftStatistics($startDate, $endDate, $courierId);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Tüm kuryeler için toplu şablon uygula
     */
    public function bulkApplyTemplate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'template' => 'required|string',
            'courier_ids' => 'required|array',
            'courier_ids.*' => 'integer|exists:couriers,id',
            'days' => 'nullable|array',
            'days.*' => 'string',
        ]);

        $successCount = 0;
        $failCount = 0;

        foreach ($validated['courier_ids'] as $courierId) {
            $courier = Courier::find($courierId);
            if ($courier) {
                $result = $this->shiftService->applyShiftTemplate(
                    $courier,
                    $validated['template'],
                    $validated['days'] ?? null
                );

                if ($result) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$successCount} kuryeye şablon uygulandı.",
            'success_count' => $successCount,
            'fail_count' => $failCount,
        ]);
    }
}
