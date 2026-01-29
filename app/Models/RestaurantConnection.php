<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'external_restaurant_id',
        'external_restaurant_name',
        'external_platform',
        'oauth_client_id',
        'webhook_url',
        'webhook_secret',
        'settings',
        'working_hours',
        'auto_accept',
        'auto_assign_courier',
        'is_active',
        'connected_at',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'working_hours' => 'array',
            'auto_accept' => 'boolean',
            'auto_assign_courier' => 'boolean',
            'is_active' => 'boolean',
            'connected_at' => 'datetime',
            'webhook_secret' => 'encrypted',
        ];
    }

    /**
     * Restoran şu an açık mı kontrol et
     */
    public function isOpen(): bool
    {
        if (!$this->working_hours) {
            return true; // Çalışma saati tanımlanmamışsa her zaman açık kabul et
        }

        $now = now();
        $dayName = strtolower($now->format('l'));

        if (!isset($this->working_hours[$dayName])) {
            return false;
        }

        $hours = $this->working_hours[$dayName];

        if (!($hours['is_open'] ?? false)) {
            return false;
        }

        $currentTime = $now->format('H:i');
        return $currentTime >= ($hours['open'] ?? '00:00') && $currentTime <= ($hours['close'] ?? '23:59');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'restaurant_connection_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('external_platform', $platform);
    }

    public function scopeForExternalRestaurant($query, string $externalId, string $platform = 'seferxyemek')
    {
        return $query->where('external_restaurant_id', $externalId)
                    ->where('external_platform', $platform);
    }

    public function generateWebhookSecret(): string
    {
        $secret = bin2hex(random_bytes(32));
        $this->update(['webhook_secret' => $secret]);
        return $secret;
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhook_secret);
        return hash_equals($expectedSignature, $signature);
    }
}
