<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Courier extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'password',
        'email',
        'photo_path',
        'tc_no',
        'vehicle_plate',
        'shifts',
        'break_durations',
        'max_delivery_minutes',
        'shift_start',
        'shift_end',
        'notification_enabled',
        'active_orders_count',
        'total_deliveries',
        'cash_balance',
        'average_delivery_time',
        'status',
        'lat',
        'lng',
        'battery_level',
        'is_charging',
        'battery_updated_at',
        'current_order_id',
        'last_login_at',
        'device_token',
        'is_app_enabled',
        'pricing_policy_id',
        // Yeni eklenen alanlar
        'platform',
        'work_type',
        'tier',
        'vat_rate',
        'withholding_rate',
        'company_name',
        'tax_office',
        'tax_number',
        'company_address',
        'iban',
        'kobi_key',
        'can_reject_package',
        'max_package_limit',
        'payment_editing_enabled',
        'status_change_enabled',
        'working_type',
        'pricing_data',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'battery_level' => 'integer',
        'is_charging' => 'boolean',
        'battery_updated_at' => 'datetime',
        'shifts' => 'array',
        'break_durations' => 'array',
        'notification_enabled' => 'boolean',
        'cash_balance' => 'decimal:2',
        'average_delivery_time' => 'decimal:2',
        'shift_start' => 'datetime:H:i',
        'shift_end' => 'datetime:H:i',
        'last_login_at' => 'datetime',
        'is_app_enabled' => 'boolean',
        'password' => 'hashed',
        // Yeni alanlar
        'vat_rate' => 'decimal:2',
        'withholding_rate' => 'decimal:2',
        'can_reject_package' => 'boolean',
        'max_package_limit' => 'integer',
        'payment_editing_enabled' => 'boolean',
        'status_change_enabled' => 'boolean',
        'pricing_data' => 'array',
    ];

    protected $appends = [
        'formatted_phone',
        'photo_url',
    ];

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_BUSY = 'busy';
    public const STATUS_OFFLINE = 'offline';
    public const STATUS_ON_BREAK = 'on_break';

    // Maksimum aktif sipariş limiti (tüm sistemde tutarlı olmalı)
    public const MAX_ACTIVE_ORDERS = 5;

    // Relationships
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

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

    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    public function pricingPolicy(): BelongsTo
    {
        return $this->belongsTo(PricingPolicy::class);
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(CourierTimeLog::class);
    }

    public function courierNotifications(): HasMany
    {
        return $this->hasMany(CourierNotification::class);
    }

    public function unreadNotifications(): HasMany
    {
        return $this->courierNotifications()->whereNull('read_at');
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

    public function scopeAppEnabled($query)
    {
        return $query->where('is_app_enabled', true);
    }

    // Methods
    /**
     * Kuryenin vardiyada olup olmadigini kontrol eder
     *
     * MANTIK HATASI DUZELTILDI:
     * - Gece yarisini gecen vardiyalar (20:00-04:00) artik dogru hesaplaniyor
     * - Ornek: 20:00-04:00 vardiyasinda saat 01:00 ise -> TRUE donmeli
     */
    public function isOnShift(): bool
    {
        if (!$this->shift_start || !$this->shift_end) {
            return true;
        }

        $now = now()->format('H:i');
        $start = $this->shift_start instanceof \Carbon\Carbon
            ? $this->shift_start->format('H:i')
            : (string) $this->shift_start;
        $end = $this->shift_end instanceof \Carbon\Carbon
            ? $this->shift_end->format('H:i')
            : (string) $this->shift_end;

        // Gece yarisini gecen vardiya kontrolu (ornek: 20:00-04:00)
        if ($end < $start) {
            // Gece vardiyasi: ya baslangictan sonra ya da bitisten once olmali
            return $now >= $start || $now <= $end;
        }

        // Normal vardiya: baslangic ve bitis arasinda olmali
        return $now >= $start && $now <= $end;
    }

    public function canReceiveNotification(): bool
    {
        return $this->notification_enabled && $this->isOnShift();
    }

    public function hasAppAccess(): bool
    {
        return $this->is_app_enabled && !empty($this->password);
    }

    public function getStatusLabel(): string
    {
        return __('statuses.courier.' . $this->status, [], 'tr') ?? $this->status;
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

    /**
     * Aktif siparis sayisini artir
     *
     * MANTIK HATASI DUZELTILDI:
     * - increment() sonrasi stale value kullaniliyordu
     * - Simdi refresh() ile guncel deger aliniyor
     */
    public function incrementActiveOrders(): void
    {
        $this->increment('active_orders_count');
        $this->refresh(); // Guncel degeri DB'den al

        if ($this->active_orders_count >= self::MAX_ACTIVE_ORDERS) {
            $this->update(['status' => self::STATUS_BUSY]);
        }
    }

    /**
     * Aktif siparis sayisini azalt
     *
     * MANTIK HATASI DUZELTILDI:
     * - decrement() sonrasi stale value kullaniliyordu
     * - Negatif deger kontrolu eklendi
     * - Simdi refresh() ile guncel deger aliniyor
     */
    public function decrementActiveOrders(): void
    {
        // Negatif deger kontrolu - 0'dan asagi dusmemeli
        if ($this->active_orders_count <= 0) {
            // Zaten 0 veya negatif, decrement yapma
            $this->update(['active_orders_count' => 0]);
            return;
        }

        $this->decrement('active_orders_count');
        $this->refresh(); // Guncel degeri DB'den al

        // BUSY durumdayken kapasite acildiysa AVAILABLE yap
        if ($this->active_orders_count < self::MAX_ACTIVE_ORDERS && $this->status === self::STATUS_BUSY) {
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

    public function goOnline(): void
    {
        $this->update([
            'status' => self::STATUS_AVAILABLE,
            'last_login_at' => now(),
        ]);
    }

    public function goOffline(): void
    {
        $this->update(['status' => self::STATUS_OFFLINE]);
    }

    public function takeBreak(): void
    {
        $this->update(['status' => self::STATUS_ON_BREAK]);
    }

    // Break duration helpers
    public function getBreakForDay(int $dayIndex): ?array
    {
        $breaks = $this->break_durations ?? [];
        return $breaks[$dayIndex] ?? null;
    }

    public function setBreakForDay(int $dayIndex, int $duration, int $parts): void
    {
        $breaks = $this->break_durations ?? [];
        $breaks[$dayIndex] = [
            'duration' => $duration,
            'parts' => $parts,
        ];
        $this->update(['break_durations' => $breaks]);
    }

    public function hasTemplateShift(): bool
    {
        return !empty($this->shifts) && is_array($this->shifts);
    }

    // Tier helpers
    public function getTierLabel(): string
    {
        return match ($this->tier) {
            'bronze' => 'Bronz',
            'silver' => 'Gümüş',
            'gold' => 'Altın',
            'platinum' => 'Platin',
            default => 'Bronz',
        };
    }

    public function getTierColor(): string
    {
        return match ($this->tier) {
            'bronze' => '#CD7F32',
            'silver' => '#C0C0C0',
            'gold' => '#FFD700',
            'platinum' => '#E5E4E2',
            default => '#CD7F32',
        };
    }

    // Work type helpers
    public function getWorkTypeLabel(): string
    {
        return match ($this->work_type) {
            'full_time' => 'Tam Zamanlı',
            'part_time' => 'Yarı Zamanlı',
            'freelance' => 'Serbest',
            default => '-',
        };
    }

    // Platform helpers
    public function getPlatformLabel(): string
    {
        return match ($this->platform) {
            'android' => 'Android',
            'ios' => 'iOS',
            default => '-',
        };
    }

    // Working type helpers
    public function getWorkingTypeLabel(): string
    {
        return match ($this->working_type) {
            'per_package' => 'Paket Basi',
            'per_km' => 'Kilometre Basi',
            'km_range' => 'Kilometre Araligi',
            'package_plus_km' => 'Paket Basi + Km Basi',
            'fixed_km_plus_km' => 'Belirli Km + Km Basi',
            'commission' => 'Komisyon Orani',
            'tiered_package' => 'Kademeli Paket Basi',
            default => 'Paket Basi',
        };
    }

    // Telefon numarasi formatlama
    public function getFormattedPhoneAttribute(): string
    {
        return \App\Helpers\PhoneFormatter::format($this->phone);
    }

    // Foto URL accessor
    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo_path) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::url($this->photo_path);
    }
}
