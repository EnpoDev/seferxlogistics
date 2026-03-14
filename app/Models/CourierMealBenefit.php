<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierMealBenefit extends Model
{
    use HasFactory;
    protected $fillable = [
        'courier_id',
        'branch_id',
        'restaurant_id',
        'benefit_date',
        'meal_type',
        'meal_value',
        'is_used',
        'used_at',
        'notes',
    ];

    protected $casts = [
        'benefit_date' => 'date',
        'meal_value' => 'decimal:2',
        'is_used' => 'boolean',
        'used_at' => 'datetime',
    ];

    /**
     * Get the courier that owns the meal benefit
     */
    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    /**
     * Get the branch/restaurant that provided the meal benefit
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Scope to get unused benefits
     */
    public function scopeUnused($query)
    {
        return $query->where('is_used', false);
    }

    /**
     * Scope to get used benefits
     */
    public function scopeUsed($query)
    {
        return $query->where('is_used', true);
    }

    /**
     * Scope to get benefits for a specific date
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('benefit_date', $date);
    }

    /**
     * Scope to get benefits for a specific meal type
     */
    public function scopeForMealType($query, $mealType)
    {
        return $query->where('meal_type', $mealType);
    }

    /**
     * Mark the benefit as used at a specific restaurant
     */
    public function markAsUsed(?int $restaurantId = null): bool
    {
        // If a restaurant ID is provided, validate against the courier's assigned restaurant
        if ($restaurantId) {
            $assignedShift = CourierMealShift::where('courier_id', $this->courier_id)
                ->whereDate('date', $this->benefit_date)
                ->where('restaurant_id', $restaurantId)
                ->where('is_active', true)
                ->first();

            if (!$assignedShift) {
                return false;
            }
        }

        $this->update([
            'is_used' => true,
            'used_at' => now(),
            'restaurant_id' => $restaurantId,
        ]);

        return true;
    }

    /**
     * Check if benefit is expired (older than today)
     */
    public function isExpired(): bool
    {
        return $this->benefit_date->isPast() && !$this->benefit_date->isToday();
    }

    /**
     * Check if benefit is valid (today or future, and not used)
     */
    public function isValid(): bool
    {
        return !$this->is_used && !$this->isExpired();
    }
}
