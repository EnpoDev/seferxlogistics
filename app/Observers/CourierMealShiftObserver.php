<?php

namespace App\Observers;

use App\Models\CourierMealBenefit;
use App\Models\CourierMealShift;

class CourierMealShiftObserver
{
    public function created(CourierMealShift $shift): void
    {
        $this->generateBenefits($shift);
    }

    public function updated(CourierMealShift $shift): void
    {
        if ($shift->wasChanged(['start_time', 'end_time', 'date', 'is_active'])) {
            // Remove unused benefits for this shift's date and regenerate
            CourierMealBenefit::where('courier_id', $shift->courier_id)
                ->whereDate('benefit_date', $shift->date)
                ->where('is_used', false)
                ->delete();

            if ($shift->is_active) {
                $this->generateBenefits($shift);
            }
        }
    }

    protected function generateBenefits(CourierMealShift $shift): void
    {
        if (!$shift->is_active) {
            return;
        }

        $duration = $shift->getDurationHours();

        // Duration rules: < 4h = 0 meals, 4-7h = 1, 7-10h = 2, 10+ = 3
        if ($duration < 4) {
            return;
        }

        $mealTypes = $this->determineMealTypes($shift, $duration);

        foreach ($mealTypes as $mealType) {
            CourierMealBenefit::firstOrCreate(
                [
                    'courier_id' => $shift->courier_id,
                    'benefit_date' => $shift->date,
                    'meal_type' => $mealType,
                ],
                [
                    'branch_id' => null,
                    'restaurant_id' => $shift->restaurant_id,
                    'meal_value' => 0,
                    'is_used' => false,
                ]
            );
        }
    }

    protected function determineMealTypes(CourierMealShift $shift, float $duration): array
    {
        $startHour = (int) $shift->getRawOriginal('start_time')
            ? (int) substr($shift->getRawOriginal('start_time'), 0, 2)
            : (int) $shift->start_time->format('H');
        $endHour = (int) ($shift->getRawOriginal('end_time')
            ? (int) substr($shift->getRawOriginal('end_time'), 0, 2)
            : (int) $shift->end_time->format('H'));

        // Handle overnight shifts
        if ($endHour < $startHour) {
            $endHour += 24;
        }

        $eligible = [];

        // Breakfast: shift covers 06:00-12:00 range
        if ($startHour <= 12 && $endHour >= 6) {
            $eligible[] = 'breakfast';
        }

        // Lunch: shift covers 11:00-15:00 range
        if ($startHour <= 15 && $endHour >= 11) {
            $eligible[] = 'lunch';
        }

        // Dinner: shift extends past 17:00
        if ($endHour >= 17) {
            $eligible[] = 'dinner';
        }

        // Limit based on duration
        $maxMeals = match (true) {
            $duration >= 10 => 3,
            $duration >= 7 => 2,
            $duration >= 4 => 1,
            default => 0,
        };

        return array_slice($eligible, 0, $maxMeals);
    }
}
