<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'user_id',
        'plan_id',
        'payment_card_id',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'cancelled_at',
        'cancel_reason',
        'next_billing_date',
        'last_payment_date',
        'last_payment_amount',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'next_billing_date' => 'datetime',
        'last_payment_date' => 'datetime',
        'last_payment_amount' => 'decimal:2',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';
    const STATUS_PENDING = 'pending';
    const STATUS_TRIAL = 'trial';
    const STATUS_PAST_DUE = 'past_due';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function paymentCard(): BelongsTo
    {
        return $this->belongsTo(PaymentCard::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeValid($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_TRIAL])
                     ->where(function ($q) {
                         $q->whereNull('ends_at')
                           ->orWhere('ends_at', '>', now());
                     });
    }

    // Methods
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && 
               ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function isOnTrial(): bool
    {
        return $this->status === self::STATUS_TRIAL && 
               $this->trial_ends_at !== null && 
               $this->trial_ends_at->isFuture();
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED ||
               ($this->ends_at !== null && $this->ends_at->isPast());
    }

    public function cancel(?string $reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
        ]);
    }

    public function renew(): bool
    {
        if (!$this->plan) {
            return false;
        }

        $newEndDate = $this->plan->billing_period === 'yearly' 
            ? now()->addYear() 
            : now()->addMonth();

        return $this->update([
            'status' => self::STATUS_ACTIVE,
            'ends_at' => $newEndDate,
            'next_billing_date' => $newEndDate,
            'cancelled_at' => null,
            'cancel_reason' => null,
        ]);
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'Aktif',
            self::STATUS_CANCELLED => 'İptal Edildi',
            self::STATUS_EXPIRED => 'Süresi Doldu',
            self::STATUS_PENDING => 'Beklemede',
            self::STATUS_TRIAL => 'Deneme',
            self::STATUS_PAST_DUE => 'Ödeme Gecikmiş',
            default => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'green',
            self::STATUS_CANCELLED => 'red',
            self::STATUS_EXPIRED => 'gray',
            self::STATUS_PENDING => 'yellow',
            self::STATUS_TRIAL => 'blue',
            self::STATUS_PAST_DUE => 'orange',
            default => 'gray',
        };
    }

    public function getDaysRemaining(): ?int
    {
        if ($this->ends_at === null) {
            return null;
        }

        return max(0, now()->diffInDays($this->ends_at, false));
    }
}

