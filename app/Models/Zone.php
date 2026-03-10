<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Zone extends Model
{
    protected $fillable = [
        'name',
        'color',
        'coordinates',
        'description',
        'is_active',
        'delivery_fee',
        'estimated_delivery_minutes',
        'daily_order_limit',
        'current_order_count',
    ];

    protected $casts = [
        'coordinates' => 'array',
        'is_active' => 'boolean',
        'delivery_fee' => 'decimal:2',
        'daily_order_limit' => 'integer',
        'current_order_count' => 'integer',
    ];

    // Relationships
    public function couriers(): BelongsToMany
    {
        return $this->belongsToMany(Courier::class)
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    // Note: Branch-Zone relationship removed as pivot table doesn't exist
    // and the relationship was not being used anywhere in the codebase

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Methods
    
    /**
     * Check if a point is inside this zone polygon
     */
    public function containsPoint(float $lat, float $lng): bool
    {
        if (!$this->coordinates || empty($this->coordinates)) {
            return false;
        }

        $polygon = $this->coordinates;
        $inside = false;
        $n = count($polygon);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $polygon[$i][0];
            $yi = $polygon[$i][1];
            $xj = $polygon[$j][0];
            $yj = $polygon[$j][1];

            if ((($yi > $lng) !== ($yj > $lng)) &&
                ($lat < ($xj - $xi) * ($lng - $yi) / ($yj - $yi) + $xi)) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * Get the center point of the zone
     */
    public function getCenterPoint(): ?array
    {
        if (!$this->coordinates || empty($this->coordinates)) {
            return null;
        }

        $latSum = 0;
        $lngSum = 0;
        $count = count($this->coordinates);

        foreach ($this->coordinates as $point) {
            $latSum += $point[0];
            $lngSum += $point[1];
        }

        return [
            'lat' => $latSum / $count,
            'lng' => $lngSum / $count,
        ];
    }

    /**
     * Get available couriers in this zone
     */
    public function getAvailableCouriers()
    {
        return $this->couriers()
            ->where('status', Courier::STATUS_AVAILABLE)
            ->get()
            ->filter(fn($courier) => $courier->isOnShift());
    }

    /**
     * Get count of assigned couriers
     * Note: Prefer using withCount('couriers') in queries for better performance
     */
    public function getCouriersCountAttribute(): int
    {
        // If loaded via withCount('couriers'), use that value
        if (array_key_exists('couriers_count', $this->attributes)) {
            return $this->attributes['couriers_count'];
        }
        // Fallback to query (avoid if possible)
        return $this->couriers()->count();
    }

    /**
     * Get the zone's delivery fee formatted
     */
    public function getFormattedDeliveryFeeAttribute(): string
    {
        return '₺' . number_format($this->delivery_fee, 2, ',', '.');
    }

    /**
     * Check if zone can accept new orders
     */
    public function canAcceptOrders(): bool
    {
        // If zone is inactive, cannot accept orders
        if (!$this->is_active) {
            return false;
        }

        // If no limit set, can always accept
        if ($this->daily_order_limit === null) {
            return true;
        }

        // Check if current count is below limit
        return $this->current_order_count < $this->daily_order_limit;
    }

    /**
     * Increment order count for this zone
     */
    public function incrementOrderCount(): void
    {
        $this->increment('current_order_count');
        $this->refresh(); // Refresh to get the updated value

        // Auto-disable if limit reached
        if ($this->daily_order_limit && $this->current_order_count >= $this->daily_order_limit) {
            $this->update(['is_active' => false]);
        }
    }

    /**
     * Reset daily order count (should be run daily via scheduler)
     */
    public function resetDailyCount(): void
    {
        $this->update([
            'current_order_count' => 0,
            'is_active' => true, // Re-enable zone
        ]);
    }

    /**
     * Get remaining order capacity
     */
    public function getRemainingCapacity(): ?int
    {
        if ($this->daily_order_limit === null) {
            return null; // Unlimited
        }

        return max(0, $this->daily_order_limit - $this->current_order_count);
    }

    /**
     * Check if zone is at capacity
     */
    public function isAtCapacity(): bool
    {
        if ($this->daily_order_limit === null) {
            return false;
        }

        return $this->current_order_count >= $this->daily_order_limit;
    }
}

