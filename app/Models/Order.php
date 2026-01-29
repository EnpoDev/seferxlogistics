<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_number',
        'tracking_token',
        'external_order_id',
        'platform',
        'user_id',
        'restaurant_connection_id',
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
        'estimated_minutes',
        'courier_assigned_at',
        'on_way_at',
        'delivery_distance',
        'cancel_reason',
        'pool_entered_at',
        'arrived_at',
        'pod_photo_path',
        'pod_timestamp',
        'pod_location',
        'pod_note',
        'scheduled_at',
        'settlement_id',
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
        'courier_assigned_at' => 'datetime',
        'on_way_at' => 'datetime',
        'pool_entered_at' => 'datetime',
        'arrived_at' => 'datetime',
        'pod_timestamp' => 'datetime',
        'pod_location' => 'array',
        'scheduled_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_PREPARING = 'preparing';
    public const STATUS_READY = 'ready';
    public const STATUS_ON_DELIVERY = 'on_delivery';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_APPROVED = 'approved'; // Bedelsiz sipariş onayı için

    // Display status constants (for UI)
    public const DISPLAY_STATUS_ASSIGNED = 'assigned';
    public const DISPLAY_STATUS_PICKED_UP = 'picked_up';
    public const DISPLAY_STATUS_ON_WAY = 'on_way';

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

    public function restaurantConnection(): BelongsTo
    {
        return $this->belongsTo(RestaurantConnection::class);
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(DailySettlement::class, 'settlement_id');
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

    public function scopeInPool($query)
    {
        return $query->where('status', self::STATUS_READY)
            ->whereNull('courier_id')
            ->whereNotNull('pool_entered_at');
    }

    public function scopePoolTimeout($query, int $minutes)
    {
        return $query->inPool()
            ->where('pool_entered_at', '<=', now()->subMinutes($minutes));
    }

    // Pool Methods
    public function isInPool(): bool
    {
        return $this->status === self::STATUS_READY
            && $this->courier_id === null
            && $this->pool_entered_at !== null;
    }

    public function poolWaitingSeconds(): ?int
    {
        if (!$this->pool_entered_at) {
            return null;
        }

        return $this->pool_entered_at->diffInSeconds(now());
    }

    public function poolWaitingMinutes(): ?int
    {
        $seconds = $this->poolWaitingSeconds();
        return $seconds !== null ? (int) floor($seconds / 60) : null;
    }

    public function enterPool(): void
    {
        $this->update(['pool_entered_at' => now()]);
    }

    public function leavePool(): void
    {
        $this->update(['pool_entered_at' => null]);
    }

    // Methods
    public function getStatusLabel(): string
    {
        return __('statuses.order.' . $this->status, [], 'tr') ?? $this->status;
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

    /**
     * Get display status for courier app
     * Maps DB status to display status for UI
     */
    public function getDisplayStatusAttribute(): string
    {
        // If ready and courier assigned, show as 'assigned'
        if ($this->status === self::STATUS_READY && $this->courier_id) {
            return self::DISPLAY_STATUS_ASSIGNED;
        }

        // If on_delivery, check timestamps for sub-status
        if ($this->status === self::STATUS_ON_DELIVERY) {
            if ($this->on_way_at) {
                return self::DISPLAY_STATUS_ON_WAY;
            }
            return self::DISPLAY_STATUS_PICKED_UP;
        }

        // Return raw status for others
        return $this->status;
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

    // Tracking Methods
    public static function generateTrackingToken(): string
    {
        do {
            $token = strtoupper(bin2hex(random_bytes(8)));
        } while (self::where('tracking_token', $token)->exists());

        return $token;
    }

    public static function findByTrackingToken(string $token): ?self
    {
        return self::where('tracking_token', $token)->first();
    }

    public function getTrackingUrl(): string
    {
        return route('tracking.show', $this->tracking_token);
    }

    public function getTrackingSteps(): array
    {
        $steps = [
            [
                'key' => 'created',
                'label' => 'Sipariş Alındı',
                'icon' => 'clipboard-check',
                'completed' => true,
                'time' => $this->created_at,
            ],
            [
                'key' => 'preparing',
                'label' => 'Hazırlanıyor',
                'icon' => 'fire',
                'completed' => in_array($this->status, ['preparing', 'ready', 'on_delivery', 'delivered']),
                'time' => $this->accepted_at,
            ],
            [
                'key' => 'ready',
                'label' => 'Hazır',
                'icon' => 'check-circle',
                'completed' => in_array($this->status, ['ready', 'on_delivery', 'delivered']),
                'time' => $this->prepared_at,
            ],
            [
                'key' => 'picked_up',
                'label' => 'Kurye Aldı',
                'icon' => 'truck',
                'completed' => in_array($this->status, ['on_delivery', 'delivered']),
                'time' => $this->picked_up_at,
            ],
            [
                'key' => 'on_way',
                'label' => 'Yolda',
                'icon' => 'navigation',
                'completed' => $this->on_way_at !== null || $this->status === 'delivered',
                'time' => $this->on_way_at,
            ],
            [
                'key' => 'delivered',
                'label' => 'Teslim Edildi',
                'icon' => 'home',
                'completed' => $this->status === 'delivered',
                'time' => $this->delivered_at,
            ],
        ];

        return $steps;
    }

    public function getCurrentStep(): string
    {
        return match ($this->status) {
            'pending' => 'created',
            'preparing' => 'preparing',
            'ready' => 'ready',
            'on_delivery' => $this->on_way_at ? 'on_way' : 'picked_up',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            default => 'created',
        };
    }

    public function getEstimatedMinutesRemaining(): ?int
    {
        if ($this->status === 'delivered' || $this->status === 'cancelled') {
            return 0;
        }

        if ($this->estimated_delivery_at) {
            $remaining = now()->diffInMinutes($this->estimated_delivery_at, false);
            return max(0, $remaining);
        }

        // Fallback tahmini
        return match ($this->status) {
            'pending' => 45,
            'preparing' => 35,
            'ready' => 25,
            'on_delivery' => $this->estimated_minutes ?? 15,
            default => null,
        };
    }

    public function getCourierLocation(): ?array
    {
        if (!$this->courier) {
            return null;
        }

        return [
            'lat' => $this->courier->lat,
            'lng' => $this->courier->lng,
            'updated_at' => $this->courier->updated_at,
        ];
    }

    public function assignCourier(Courier $courier): void
    {
        $this->update([
            'courier_id' => $courier->id,
            'courier_assigned_at' => now(),
            'pool_entered_at' => null,
        ]);
    }

    public function markPickedUp(): void
    {
        $this->update([
            'status' => self::STATUS_ON_DELIVERY,
            'picked_up_at' => now(),
        ]);
    }

    public function markOnWay(): void
    {
        $this->update([
            'on_way_at' => now(),
        ]);
    }

    public function markDelivered(): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);

        // Nakit ödemeli siparişlerde kuryenin bakiyesini güncelle
        $this->updateCourierCashBalance();
    }

    /**
     * Nakit ödemeli siparişlerde kuryenin bakiyesini güncelle
     */
    public function updateCourierCashBalance(): void
    {
        // Sadece nakit ödemeli ve teslim edilmiş siparişler için
        if ($this->payment_method !== self::PAYMENT_CASH || $this->status !== self::STATUS_DELIVERED) {
            return;
        }

        // Kurye atanmış olmalı
        if (!$this->courier_id) {
            return;
        }

        $courier = $this->courier;
        if ($courier) {
            // Kuryenin nakit bakiyesini artır (toplanan nakit)
            $courier->increment('cash_balance', $this->total);
        }
    }

    // POD (Proof of Delivery) Methods
    public function hasPod(): bool
    {
        return $this->pod_photo_path !== null;
    }

    public function getPodPhotoUrl(): ?string
    {
        if (!$this->pod_photo_path) {
            return null;
        }

        return \Storage::disk('public')->url($this->pod_photo_path);
    }

    public function savePod(string $photoPath, ?array $location = null, ?string $note = null): void
    {
        $this->update([
            'pod_photo_path' => $photoPath,
            'pod_timestamp' => now(),
            'pod_location' => $location,
            'pod_note' => $note,
        ]);
    }

    public function getPodInfo(): ?array
    {
        if (!$this->hasPod()) {
            return null;
        }

        return [
            'photo_url' => $this->getPodPhotoUrl(),
            'timestamp' => $this->pod_timestamp?->format('d.m.Y H:i:s'),
            'location' => $this->pod_location,
            'note' => $this->pod_note,
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->tracking_token)) {
                $order->tracking_token = self::generateTrackingToken();
            }
        });
    }
}
