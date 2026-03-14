<?php

namespace App\Http\Controllers\Bayi;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\CourierMealShift;
use App\Models\User;
use Illuminate\Http\Request;

class BayiShiftController extends Controller
{
    /**
     * Get bayi and all child işletme user IDs for courier filtering
     */
    private function getBayiAndChildUserIds()
    {
        $user = auth()->user();
        $userIds = [$user->id];

        // If user is a bayi, include all child işletme IDs
        if ($user->parent_id === null) { // This is a bayi (parent dealer)
            $childIds = User::where('parent_id', $user->id)->pluck('id');
            $userIds = array_merge($userIds, $childIds->toArray());
        }

        return $userIds;
    }

    public function vardiyaSaatleri(Request $request)
    {
        $query = Courier::whereIn('user_id', $this->getBayiAndChildUserIds())
            ->orderBy('name');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('tc_no', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('vehicle_plate', 'like', "%{$search}%");
            });
        }

        $couriers = $query->paginate(50);
        $businessInfo = \App\Models\BusinessInfo::first();

        return view('bayi.vardiya-saatleri', compact('couriers', 'businessInfo'));
    }

    /**
     * Kurye sahiplik kontrolu - bayinin kuryesi mi?
     */
    private function checkCourierOwnership(Courier $courier): void
    {
        if (!in_array($courier->user_id, $this->getBayiAndChildUserIds())) {
            abort(403, 'Bu kuryeye erişim yetkiniz yok.');
        }
    }

    public function vardiyaGuncelle(Request $request, Courier $courier)
    {
        $this->checkCourierOwnership($courier);

        $validated = $request->validate([
            'day' => 'required|integer|min:0|max:6',
            'hours' => 'nullable|string|max:50',
            'break_duration' => 'nullable|integer|min:0',
            'break_parts' => 'nullable|integer|min:0',
        ]);

        $shifts = $courier->shifts ?? [];
        $shifts[$validated['day']] = $validated['hours'];

        $breakDurations = $courier->break_durations ?? [];
        if ($request->filled('break_duration') && $request->filled('break_parts')) {
            $breakDurations[$validated['day']] = [
                'duration' => $validated['break_duration'],
                'parts' => $validated['break_parts'],
            ];
        }

        $courier->shifts = $shifts;
        $courier->break_durations = $breakDurations;
        $courier->save();

        return response()->json(['success' => true]);
    }

    public function vardiyaVarsayilanKaydet(Request $request)
    {
        $validated = $request->validate([
            'default_shifts' => 'nullable|array',
            'default_break_duration' => 'nullable|integer|min:0',
            'default_break_parts' => 'nullable|integer|min:0',
            'auto_assign_shifts' => 'boolean'
        ]);

        $businessInfo = \App\Models\BusinessInfo::first();
        if (!$businessInfo) {
            $businessInfo = \App\Models\BusinessInfo::create([
                'name' => 'Default Business',
                'phone' => '',
                'email' => '',
                'address' => ''
            ]);
        }

        $businessInfo->update([
            'default_shifts' => $request->default_shifts,
            'default_break_duration' => $request->default_break_duration ?? 60,
            'default_break_parts' => $request->default_break_parts ?? 2,
            'auto_assign_shifts' => $request->has('auto_assign_shifts')
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('messages.success.default_shift_updated'));
    }

    public function vardiyaTopluGuncelle(Request $request)
    {
        $validated = $request->validate([
            'shifts' => 'required|array',
            'break_durations' => 'nullable|array',
            'courier_ids' => 'nullable|array',
            'apply_to_all' => 'boolean'
        ]);

        $query = Courier::whereIn('user_id', $this->getBayiAndChildUserIds());

        if ($request->apply_to_all) {
            // Apply to all couriers (already filtered by user_id)
        } else {
             if (empty($request->courier_ids)) {
                 return back()->with('error', __('messages.error.select_at_least_one_courier'));
             }
             $query->whereIn('id', $request->courier_ids);
        }

        $shiftsToUpdate = $request->shifts;
        $breaksToUpdate = $request->break_durations ?? [];

        // NOTE: This bulk update does not have optimistic locking or concurrent modification control.
        // If two admins update shifts simultaneously, the last write wins without conflict detection.
        // Consider adding updated_at checks or locking mechanism if concurrent edits become an issue.

        $couriers = $query->get();
        foreach ($couriers as $courier) {
            $currentShifts = $courier->shifts ?? [];
            $currentBreaks = $courier->break_durations ?? [];

            // Validate and update shifts (only accept day indexes 0-6)
            foreach ($shiftsToUpdate as $day => $time) {
                if (!is_numeric($day) || $day < 0 || $day > 6) {
                    \Log::warning('Invalid shift day index attempted', ['day' => $day, 'courier_id' => $courier->id]);
                    continue;
                }
                if (!is_null($time) && $time !== '') {
                    $currentShifts[$day] = $time;
                }
            }

            // Validate and update breaks (only accept day indexes 0-6)
            foreach ($breaksToUpdate as $day => $breakData) {
                if (!is_numeric($day) || $day < 0 || $day > 6) {
                    \Log::warning('Invalid break day index attempted', ['day' => $day, 'courier_id' => $courier->id]);
                    continue;
                }
                if (isset($breakData['duration']) && isset($breakData['parts'])) {
                    $currentBreaks[$day] = [
                        'duration' => $breakData['duration'],
                        'parts' => $breakData['parts'],
                    ];
                }
            }

            $courier->shifts = $currentShifts;
            $courier->break_durations = $currentBreaks;
            $courier->save();
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('messages.success.courier_shift_updated'));
    }

    public function vardiyaSil(Request $request, Courier $courier)
    {
        $this->checkCourierOwnership($courier);

        $validated = $request->validate([
            'day' => 'required|integer|min:0|max:6',
        ]);

        $shifts = $courier->shifts ?? [];
        $breakDurations = $courier->break_durations ?? [];

        unset($shifts[$validated['day']]);
        unset($breakDurations[$validated['day']]);

        $courier->shifts = $shifts;
        $courier->break_durations = $breakDurations;
        $courier->save();

        return response()->json(['success' => true]);
    }

    public function vardiyaKopyala(Request $request, Courier $courier)
    {
        $this->checkCourierOwnership($courier);

        $validated = $request->validate([
            'source_day' => 'required|integer|min:0|max:6',
            'target_days' => 'required|array',
            'target_days.*' => 'integer|min:0|max:6',
        ]);

        $shifts = $courier->shifts ?? [];
        $breakDurations = $courier->break_durations ?? [];

        $sourceShift = $shifts[$validated['source_day']] ?? null;
        $sourceBreak = $breakDurations[$validated['source_day']] ?? null;

        foreach ($validated['target_days'] as $targetDay) {
            if ($sourceShift) {
                $shifts[$targetDay] = $sourceShift;
            }
            if ($sourceBreak) {
                $breakDurations[$targetDay] = $sourceBreak;
            }
        }

        $courier->shifts = $shifts;
        $courier->break_durations = $breakDurations;
        $courier->save();

        return response()->json(['success' => true]);
    }

    public function vardiyaSablonUygula(Request $request, Courier $courier)
    {
        $this->checkCourierOwnership($courier);

        $businessInfo = \App\Models\BusinessInfo::first();

        if (!$businessInfo || !$businessInfo->default_shifts) {
            return response()->json(['success' => false, 'message' => 'Varsayılan şablon bulunamadı.'], 404);
        }

        $courier->shifts = $businessInfo->default_shifts;

        // Apply default breaks to all days
        $breakDurations = [];
        for ($i = 0; $i < 7; $i++) {
            if (isset($businessInfo->default_shifts[$i])) {
                $breakDurations[$i] = [
                    'duration' => $businessInfo->default_break_duration ?? 60,
                    'parts' => $businessInfo->default_break_parts ?? 2,
                ];
            }
        }

        $courier->break_durations = $breakDurations;
        $courier->save();

        return response()->json(['success' => true]);
    }

    // ============================================
    // MEAL SHIFT (Yemek Vardiyası) CRUD
    // ============================================

    public function mealShifts(Request $request)
    {
        $weekOffset = (int) $request->query('week', 0);
        $startOfWeek = now()->startOfWeek()->addWeeks($weekOffset);
        $endOfWeek = $startOfWeek->copy()->endOfWeek();

        $userIds = $this->getBayiAndChildUserIds();
        $couriers = Courier::whereIn('user_id', $userIds)->orderBy('name')->get();

        $mealShifts = CourierMealShift::whereIn('courier_id', $couriers->pluck('id'))
            ->with('restaurant')
            ->whereBetween('date', [$startOfWeek->format('Y-m-d'), $endOfWeek->format('Y-m-d')])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->groupBy('courier_id');

        $restaurants = \App\Models\Restaurant::where('is_active', true)->orderBy('name')->get();

        return view('bayi.yemek-vardiyalari', compact(
            'couriers', 'mealShifts', 'restaurants', 'startOfWeek', 'endOfWeek', 'weekOffset'
        ));
    }

    public function mealShiftStore(Request $request)
    {
        $validated = $request->validate([
            'courier_id' => 'required|exists:couriers,id',
            'restaurant_id' => 'nullable|exists:restaurants,id',
            'date' => 'required|date',
            'meal_type' => 'required|in:breakfast,lunch,dinner',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string|max:500',
        ]);

        $courier = Courier::findOrFail($validated['courier_id']);
        $this->checkCourierOwnership($courier);

        $mealShift = CourierMealShift::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Yemek vardiyası eklendi.',
            'meal_shift' => $mealShift,
        ]);
    }

    public function mealShiftUpdate(Request $request, CourierMealShift $mealShift)
    {
        $this->checkCourierOwnership($mealShift->courier);

        $validated = $request->validate([
            'restaurant_id' => 'nullable|exists:restaurants,id',
            'date' => 'sometimes|date',
            'meal_type' => 'sometimes|in:breakfast,lunch,dinner',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',
            'is_active' => 'sometimes|boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $mealShift->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Yemek vardiyası güncellendi.',
            'meal_shift' => $mealShift,
        ]);
    }

    public function mealShiftDestroy(CourierMealShift $mealShift)
    {
        $this->checkCourierOwnership($mealShift->courier);

        $mealShift->delete();

        return response()->json([
            'success' => true,
            'message' => 'Yemek vardiyası silindi.',
        ]);
    }

    public function getMealCostReport(Request $request)
    {
        $startDate = $request->input('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $userId = auth()->id();

        $benefits = \App\Models\CourierMealBenefit::with(['courier', 'restaurant'])
            ->whereHas('courier', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->whereBetween('benefit_date', [$startDate, $endDate])
            ->where('is_used', true)
            ->get();

        $report = $benefits->groupBy('courier_id')->map(function ($courierBenefits) {
            $courier = $courierBenefits->first()->courier;

            $byMealType = $courierBenefits->groupBy('meal_type')->map(function ($typeBenefits, $type) {
                return [
                    'meal_type' => $type,
                    'count' => $typeBenefits->count(),
                    'total_value' => round($typeBenefits->sum('meal_value'), 2),
                ];
            })->values();

            return [
                'courier_id' => $courier?->id,
                'courier_name' => $courier?->name ?? 'Bilinmeyen',
                'total_benefits' => $courierBenefits->count(),
                'total_cost' => round($courierBenefits->sum('meal_value'), 2),
                'by_meal_type' => $byMealType,
            ];
        })->sortByDesc('total_cost')->values();

        $grandTotal = round($benefits->sum('meal_value'), 2);

        return response()->json([
            'success' => true,
            'period' => ['start' => $startDate, 'end' => $endDate],
            'grand_total' => $grandTotal,
            'couriers' => $report,
        ]);
    }
}
