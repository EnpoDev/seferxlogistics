<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'customer_id',
        'courier_id',
        'branch_id',
        'restaurant_id',
        'customer_name',
        'customer_phone',
        'customer_address',
        'lat',
        'lng',
        'subtotal',
        'delivery_fee',
        'total',
        'payment_method',
        'is_paid',
        'status',
        'notes',
        'accepted_at',
        'prepared_at',
        'picked_up_at',
        'delivered_at',
        'cancelled_at',
        'estimated_delivery_at',
        'delivery_distance',
        'cancel_reason',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'subtotal' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'total' => 'decimal:2',
        'is_paid' => 'boolean',
        'accepted_at' => 'datetime',
        'prepared_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'estimated_delivery_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_READY = 'ready';
    public const STATUS_ON_DELIVERY = 'on_delivery';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_CASH = 'cash';
    public const PAYMENT_CARD = 'card';
    public const PAYMENT_ONLINE = 'online';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_PREPARING,
            self::STATUS_READY,
            self::STATUS_ON_DELIVERY,
        ]);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [self::STATUS_DELIVERED, self::STATUS_CANCELLED]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // Methods
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Beklemede',
            self::STATUS_PREPARING => 'Hazırlanıyor',
            self::STATUS_READY => 'Hazır',
            self::STATUS_ON_DELIVERY => 'Yolda',
            self::STATUS_DELIVERED => 'Teslim Edildi',
            self::STATUS_CANCELLED => 'İptal',
            default => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_PREPARING => 'blue',
            self::STATUS_READY => 'purple',
            self::STATUS_ON_DELIVERY => 'orange',
            self::STATUS_DELIVERED => 'green',
            self::STATUS_CANCELLED => 'red',
            default => 'gray',
        };
    }

    public function getPaymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            self::PAYMENT_CASH => 'Nakit',
            self::PAYMENT_CARD => 'Kredi Kartı',
            self::PAYMENT_ONLINE => 'Online',
            default => $this->payment_method,
        };
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PREPARING]);
    }

    public function getDeliveryTimeInMinutes(): ?int
    {
        if (!$this->delivered_at || !$this->created_at) {
            return null;
        }
        
        return $this->created_at->diffInMinutes($this->delivered_at);
    }

    public function updateCustomerStats(): void
    {
        if ($this->customer) {
            $this->customer->updateOrderStats();
        }
    }

    public static function generateOrderNumber(): string
    {
        $lastOrder = self::latest('id')->first();
        $nextId = $lastOrder ? $lastOrder->id + 1 : 1;
        return 'ORD-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);
    }
}
