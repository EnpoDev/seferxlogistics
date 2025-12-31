<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'branch_id',
        'subscription_id',
        'payment_card_id',
        'type',
        'amount',
        'currency',
        'status',
        'description',
        'gateway',
        'gateway_transaction_id',
        'gateway_response',
        'invoice_number',
        'invoice_url',
        'paid_at',
        'refunded_at',
        'refund_amount',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'gateway_response' => 'array',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    const TYPE_SUBSCRIPTION = 'subscription';
    const TYPE_ONE_TIME = 'one_time';
    const TYPE_REFUND = 'refund';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_BALANCE_ADDITION = 'balance_addition';
    const TYPE_COMMISSION = 'commission';

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';
    const STATUS_CANCELLED = 'cancelled';

    const CURRENCY_TRY = 'TRY';
    const CURRENCY_USD = 'USD';
    const CURRENCY_EUR = 'EUR';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function paymentCard(): BelongsTo
    {
        return $this->belongsTo(PaymentCard::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Methods
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isRefunded(): bool
    {
        return in_array($this->status, [self::STATUS_REFUNDED, self::STATUS_PARTIALLY_REFUNDED]);
    }

    public function canBeRefunded(): bool
    {
        return $this->status === self::STATUS_COMPLETED && 
               $this->refund_amount === null &&
               $this->paid_at !== null &&
               $this->paid_at->diffInDays(now()) <= 30;
    }

    public function markAsCompleted(): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'paid_at' => now(),
        ]);
    }

    public function markAsFailed(?string $reason = null): bool
    {
        $metadata = $this->metadata ?? [];
        $metadata['failure_reason'] = $reason;

        return $this->update([
            'status' => self::STATUS_FAILED,
            'metadata' => $metadata,
        ]);
    }

    public function refund(?float $amount = null): bool
    {
        $refundAmount = $amount ?? $this->amount;
        $isPartial = $amount !== null && $amount < $this->amount;

        return $this->update([
            'status' => $isPartial ? self::STATUS_PARTIALLY_REFUNDED : self::STATUS_REFUNDED,
            'refunded_at' => now(),
            'refund_amount' => $refundAmount,
        ]);
    }

    public function getFormattedAmount(): string
    {
        $symbol = match ($this->currency) {
            self::CURRENCY_TRY => '₺',
            self::CURRENCY_USD => '$',
            self::CURRENCY_EUR => '€',
            default => $this->currency . ' ',
        };

        return $symbol . number_format($this->amount, 2, ',', '.');
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Beklemede',
            self::STATUS_COMPLETED => 'Tamamlandı',
            self::STATUS_FAILED => 'Başarısız',
            self::STATUS_REFUNDED => 'İade Edildi',
            self::STATUS_PARTIALLY_REFUNDED => 'Kısmi İade',
            self::STATUS_CANCELLED => 'İptal Edildi',
            default => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_FAILED => 'red',
            self::STATUS_REFUNDED => 'blue',
            self::STATUS_PARTIALLY_REFUNDED => 'blue',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray',
        };
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_SUBSCRIPTION => 'Abonelik',
            self::TYPE_ONE_TIME => 'Tek Seferlik',
            self::TYPE_REFUND => 'Iade',
            self::TYPE_ADJUSTMENT => 'Duzeltme',
            self::TYPE_BALANCE_ADDITION => 'Bakiye Ekleme',
            self::TYPE_COMMISSION => 'Komisyon',
            default => $this->type,
        };
    }

    /**
     * Generate invoice number
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = date('Y');
        $month = date('m');
        
        $lastInvoice = self::where('invoice_number', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastInvoice) {
            $parts = explode('-', $lastInvoice->invoice_number);
            $sequence = (int) end($parts) + 1;
        } else {
            $sequence = 1;
        }

        return sprintf('%s-%s%s-%06d', $prefix, $year, $month, $sequence);
    }
}

