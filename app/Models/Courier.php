<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Courier extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'photo_path',
        'tc_no',
        'vehicle_plate',
        'shifts',
        'max_delivery_minutes',
        'shift_start',
        'shift_end',
        'notification_enabled',
        'active_orders_count',
        'total_deliveries',
        'average_delivery_time',
        'status',
        'lat',
        'lng',
        'current_order_id',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'shifts' => 'array',
        'notification_enabled' => 'boolean',
        'average_delivery_time' => 'decimal:2',
        'shift_start' => 'datetime:H:i',
        'shift_end' => 'datetime:H:i',
    ];

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_BUSY = 'busy';
    public const STATUS_OFFLINE = 'offline';
    public const STATUS_ON_BREAK = 'on_break';

    // Relationships
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function currentOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'current_order_id');
    }

    public function activeOrders(): HasMany
    {
        return $this->orders()->active();
    }

    public function zones(): BelongsToMany
    {
        return $this->belongsToMany(Zone::class)
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function primaryZone()
    {
        return $this->zones()->wherePivot('is_primary', true)->first();
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function scopeOnShift($query)
    {
        $now = now()->format('H:i:s');
        return $query->where(function ($q) use ($now) {
            $q->whereNull('shift_start')
              ->orWhere(function ($q2) use ($now) {
                  $q2->where('shift_start', '<=', $now)
                     ->where('shift_end', '>=', $now);
              });
        });
    }

    public function scopeCanReceiveNotifications($query)
    {
        return $query->where('notification_enabled', true)->onShift();
    }

    // Methods
    public function isOnShift(): bool
    {
        if (!$this->shift_start || !$this->shift_end) {
            return true;
        }

        $now = now()->format('H:i');
        $start = is_string($this->shift_start) ? $this->shift_start : $this->shift_start->format('H:i');
        $end = is_string($this->shift_end) ? $this->shift_end : $this->shift_end->format('H:i');
        
        return $now >= $start && $now <= $end;
    }

    public function canReceiveNotification(): bool
    {
        return $this->notification_enabled && $this->isOnShift();
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_AVAILABLE => 'Müsait',
            self::STATUS_BUSY => 'Meşgul',
            self::STATUS_OFFLINE => 'Çevrimdışı',
            self::STATUS_ON_BREAK => 'Molada',
            default => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_AVAILABLE => 'green',
            self::STATUS_BUSY => 'orange',
            self::STATUS_OFFLINE => 'gray',
            self::STATUS_ON_BREAK => 'yellow',
            default => 'gray',
        };
    }

    public function incrementActiveOrders(): void
    {
        $this->increment('active_orders_count');
        
        if ($this->active_orders_count >= 3) {
            $this->update(['status' => self::STATUS_BUSY]);
        }
    }

    public function decrementActiveOrders(): void
    {
        $this->decrement('active_orders_count');
        
        if ($this->active_orders_count < 3 && $this->status === self::STATUS_BUSY) {
            $this->update(['status' => self::STATUS_AVAILABLE]);
        }
    }

    public function recordDelivery(int $deliveryMinutes): void
    {
        $this->increment('total_deliveries');
        
        // Update average delivery time
        $totalTime = ($this->average_delivery_time ?? 0) * ($this->total_deliveries - 1);
        $newAverage = ($totalTime + $deliveryMinutes) / $this->total_deliveries;
        
        $this->update(['average_delivery_time' => $newAverage]);
    }

    public function calculateDistanceTo(float $lat, float $lng): float
    {
        if (!$this->lat || !$this->lng) {
            return PHP_FLOAT_MAX;
        }

        // Haversine formula
        $earthRadius = 6371000; // meters
        
        $latFrom = deg2rad($this->lat);
        $lngFrom = deg2rad($this->lng);
        $latTo = deg2rad($lat);
        $lngTo = deg2rad($lng);
        
        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;
        
        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lngDelta / 2), 2)
        ));
        
        return $angle * $earthRadius;
    }
}
