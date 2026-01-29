<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransaction extends Model
{
    protected $fillable = [
        'courier_id',
        'branch_id',
        'created_by',
        'amount',
        'type',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public const TYPE_PAYMENT_RECEIVED = 'payment_received';
    public const TYPE_ADVANCE_GIVEN = 'advance_given';

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    // Relationships
    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Methods
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_PAYMENT_RECEIVED => 'Ödeme Al',
            self::TYPE_ADVANCE_GIVEN => 'Avans Ver',
            default => $this->type,
        };
    }

    public function getStatusLabel(): string
    {
        return __('statuses.cash_transaction.' . $this->status, [], 'tr') ?? $this->status;
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_CANCELLED => 'red',
            default => 'gray',
        };
    }

    /**
     * Apply transaction to courier balance
     */
    public function applyToBalance(): void
    {
        if ($this->status !== self::STATUS_COMPLETED) {
            return;
        }

        $courier = $this->courier;

        if (!$courier) {
            return;
        }

        if ($this->type === self::TYPE_PAYMENT_RECEIVED) {
            // Ödeme alındı - bakiyeyi azalt
            $courier->decrement('cash_balance', $this->amount);
        } else {
            // Avans verildi - bakiyeyi artır
            $courier->increment('cash_balance', $this->amount);
        }
    }

    /**
     * Reverse transaction from courier balance
     */
    public function reverseFromBalance(): void
    {
        $courier = $this->courier;

        if (!$courier) {
            return;
        }

        if ($this->type === self::TYPE_PAYMENT_RECEIVED) {
            // Ödeme alımını geri al - bakiyeyi artır
            $courier->increment('cash_balance', $this->amount);
        } else {
            // Avans vermeyi geri al - bakiyeyi azalt
            $courier->decrement('cash_balance', $this->amount);
        }
    }
}
