<?php

namespace App\Http\Controllers\Bayi;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use Illuminate\Http\Request;

class BayiShiftController extends Controller
{
    public function vardiyaSaatleri(Request $request)
    {
        $query = Courier::orderBy('name');

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

    public function vardiyaGuncelle(Request $request, Courier $courier)
    {
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

        $query = Courier::query();

        if ($request->apply_to_all) {
            // Apply to all couriers
        } else {
             if (empty($request->courier_ids)) {
                 return back()->with('error', __('messages.error.select_at_least_one_courier'));
             }
             $query->whereIn('id', $request->courier_ids);
        }

        $shiftsToUpdate = $request->shifts;
        $breaksToUpdate = $request->break_durations ?? [];

        $couriers = $query->get();
        foreach ($couriers as $courier) {
            $currentShifts = $courier->shifts ?? [];
            $currentBreaks = $courier->break_durations ?? [];

            foreach ($shiftsToUpdate as $day => $time) {
                if (!is_null($time) && $time !== '') {
                    $currentShifts[$day] = $time;
                }
            }

            foreach ($breaksToUpdate as $day => $breakData) {
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
}
