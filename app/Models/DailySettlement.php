<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class DailySettlement extends Model
{
    protected $fillable = [
        'branch_id',
        'restaurant_connection_id',
        'settlement_date',
        'order_count',
        'total_revenue',
        'delivery_fee_total',
        'restaurant_share',
        'branch_commission',
        'courier_earnings',
        'branch_delivery_share',
        'commission_rate_used',
        'courier_rate_used',
        'status',
        'notes',
        'order_ids',
    ];

    protected $casts = [
        'settlement_date' => 'date',
        'order_count' => 'integer',
        'total_revenue' => 'decimal:2',
        'delivery_fee_total' => 'decimal:2',
        'restaurant_share' => 'decimal:2',
        'branch_commission' => 'decimal:2',
        'courier_earnings' => 'decimal:2',
        'branch_delivery_share' => 'decimal:2',
        'commission_rate_used' => 'decimal:2',
        'courier_rate_used' => 'decimal:2',
        'order_ids' => 'array',
    ];

    // Status sabitleri
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function restaurantConnection(): BelongsTo
    {
        return $this->belongsTo(RestaurantConnection::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'settlement_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeForDate($query, Carbon $date)
    {
        return $query->whereDate('settlement_date', $date);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForRestaurant($query, ?int $restaurantConnectionId)
    {
        if ($restaurantConnectionId === null) {
            return $query->whereNull('restaurant_connection_id');
        }
        return $query->where('restaurant_connection_id', $restaurantConnectionId);
    }

    public function scopeDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('settlement_date', [$startDate, $endDate]);
    }

    // Accessors
    public function getTotalBranchEarningsAttribute(): float
    {
        return (float) $this->branch_commission + (float) $this->branch_delivery_share;
    }

    public function getRestaurantNameAttribute(): string
    {
        if ($this->restaurantConnection) {
            return $this->restaurantConnection->external_restaurant_name ?? 'Bilinmeyen Restoran';
        }
        return 'İç Siparişler';
    }

    public function getIsInternalAttribute(): bool
    {
        return $this->restaurant_connection_id === null;
    }

    // Methods
    public function approve(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->update(['status' => self::STATUS_APPROVED]);
        return true;
    }

    public function markAsPaid(?string $notes = null): bool
    {
        if (!in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED])) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_PAID,
            'notes' => $notes ?? $this->notes,
        ]);
        return true;
    }

    public function cancel(?string $reason = null): bool
    {
        if ($this->status === self::STATUS_PAID) {
            return false; // Ödenmiş settlement iptal edilemez
        }

        // Siparişlerin settlement_id'lerini temizle
        Order::whereIn('id', $this->order_ids ?? [])->update(['settlement_id' => null]);

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'notes' => $reason ?? $this->notes,
        ]);

        return true;
    }

    /**
     * Özet bilgileri döndürür
     */
    public function getSummary(): array
    {
        return [
            'settlement_id' => $this->id,
            'date' => $this->settlement_date->format('Y-m-d'),
            'restaurant' => $this->restaurant_name,
            'is_internal' => $this->is_internal,
            'order_count' => $this->order_count,
            'total_revenue' => number_format($this->total_revenue, 2),
            'delivery_fees' => number_format($this->delivery_fee_total, 2),
            'restaurant_share' => number_format($this->restaurant_share, 2),
            'branch_commission' => number_format($this->branch_commission, 2),
            'courier_earnings' => number_format($this->courier_earnings, 2),
            'branch_delivery_share' => number_format($this->branch_delivery_share, 2),
            'total_branch_earnings' => number_format($this->total_branch_earnings, 2),
            'commission_rate' => $this->commission_rate_used . '%',
            'courier_rate' => $this->courier_rate_used . '%',
            'status' => $this->status,
        ];
    }
}
