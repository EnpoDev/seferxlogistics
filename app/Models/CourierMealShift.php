<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierMealShift extends Model
{
    use HasFactory;
    protected $fillable = [
        'courier_id',
        'date',
        'meal_type',
        'start_time',
        'end_time',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get start_time as Carbon instance for comparisons
     */
    protected function getStartTimeAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value) : null;
    }

    /**
     * Get end_time as Carbon instance for comparisons
     */
    protected function getEndTimeAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value) : null;
    }

    /**
     * Get the courier that owns the meal shift
     */
    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    /**
     * Scope to get active meal shifts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get meal shifts for a specific date
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope to get meal shifts for a specific meal type
     */
    public function scopeForMealType($query, $mealType)
    {
        return $query->where('meal_type', $mealType);
    }

    /**
     * Check if the meal shift is currently active
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        $currentDate = $now->format('Y-m-d');
        $currentTime = $now->format('H:i');

        // Check if today
        if ($this->date->format('Y-m-d') !== $currentDate) {
            return false;
        }

        // Check if within time range
        $startTime = $this->start_time->format('H:i');
        $endTime = $this->end_time->format('H:i');

        if ($endTime < $startTime) {
            // Overnight shift
            return $currentTime >= $startTime || $currentTime <= $endTime;
        }

        return $currentTime >= $startTime && $currentTime <= $endTime;
    }

    /**
     * Get duration in hours
     */
    public function getDurationHours(): float
    {
        $start = $this->start_time;
        $end = $this->end_time;

        if ($end < $start) {
            // Overnight shift - add 24 hours to end time
            $end = $end->copy()->addDay();
        }

        return $start->diffInHours($end, true);
    }
}
