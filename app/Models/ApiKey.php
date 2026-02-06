<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'key',
        'prefix',
        'last_used_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'key',
    ];

    /**
     * Get the user that owns this API key
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a new API key
     * Returns the plain text key (only shown once)
     */
    public static function generate(string $name, ?int $userId = null, ?\DateTime $expiresAt = null): array
    {
        $plainKey = 'sfrx_' . Str::random(40);
        $prefix = substr($plainKey, 0, 8);

        $apiKey = self::create([
            'user_id' => $userId,
            'name' => $name,
            'key' => hash('sha256', $plainKey),
            'prefix' => $prefix,
            'expires_at' => $expiresAt,
            'is_active' => true,
        ]);

        return [
            'api_key' => $apiKey,
            'plain_key' => $plainKey, // Only returned once!
        ];
    }

    /**
     * Find API key by plain text key
     */
    public static function findByKey(string $plainKey): ?self
    {
        $hashedKey = hash('sha256', $plainKey);
        $prefix = substr($plainKey, 0, 8);

        return self::where('prefix', $prefix)
            ->where('key', $hashedKey)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Check if the key is valid
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Mark the key as used
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Revoke the API key
     */
    public function revoke(): void
    {
        $this->update(['is_active' => false]);
    }
}
