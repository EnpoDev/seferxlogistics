<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Integration extends Model
{
    protected $fillable = [
        'platform',
        'name',
        'description',
        'credentials',
        'settings',
        'is_active',
        'is_connected',
        'last_sync_at',
        'status',
        'error_message',
        'webhook_url',
        'webhook_secret',
    ];

    protected $casts = [
        'credentials' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'is_connected' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = [
        'credentials',
        'webhook_secret',
    ];

    const PLATFORM_YEMEKSEPETI = 'yemeksepeti';
    const PLATFORM_GETIR = 'getir';
    const PLATFORM_TRENDYOL = 'trendyol';

    const STATUS_INACTIVE = 'inactive';
    const STATUS_CONNECTING = 'connecting';
    const STATUS_CONNECTED = 'connected';
    const STATUS_ERROR = 'error';

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeConnected($query)
    {
        return $query->where('is_connected', true);
    }

    // Methods
    public function getStatusLabel(): string
    {
        return __('statuses.integration.' . $this->status, [], 'tr') ?? $this->status;
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            self::STATUS_INACTIVE => 'gray',
            self::STATUS_CONNECTING => 'yellow',
            self::STATUS_CONNECTED => 'green',
            self::STATUS_ERROR => 'red',
            default => 'gray',
        };
    }

    public function getPlatformLabel(): string
    {
        return match ($this->platform) {
            self::PLATFORM_YEMEKSEPETI => 'Yemeksepeti',
            self::PLATFORM_GETIR => 'Getir Yemek',
            self::PLATFORM_TRENDYOL => 'Trendyol Yemek',
            default => ucfirst($this->platform),
        };
    }

    public function getPlatformLogo(): string
    {
        return match ($this->platform) {
            self::PLATFORM_YEMEKSEPETI => '/images/integrations/yemeksepeti.png',
            self::PLATFORM_GETIR => '/images/integrations/getir.png',
            self::PLATFORM_TRENDYOL => '/images/integrations/trendyol.png',
            default => '',
        };
    }

    /**
     * Set encrypted credentials
     */
    public function setCredentialsAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['credentials'] = Crypt::encryptString(json_encode($value));
        } else {
            $this->attributes['credentials'] = $value;
        }
    }

    /**
     * Get decrypted credentials
     */
    public function getCredentialsAttribute($value)
    {
        if ($value) {
            try {
                return json_decode(Crypt::decryptString($value), true);
            } catch (\Exception $e) {
                // If already JSON (not encrypted), just decode
                return json_decode($value, true);
            }
        }
        return null;
    }

    /**
     * Generate a unique webhook URL for this integration
     */
    public function generateWebhookUrl(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->update([
            'webhook_url' => url("/webhooks/{$this->platform}/{$token}"),
            'webhook_secret' => $token,
        ]);
        return $this->webhook_url;
    }

    /**
     * Mark as connected
     */
    public function markAsConnected(): void
    {
        $this->update([
            'is_connected' => true,
            'status' => self::STATUS_CONNECTED,
            'error_message' => null,
            'last_sync_at' => now(),
        ]);
    }

    /**
     * Mark as error
     */
    public function markAsError(string $message): void
    {
        $this->update([
            'is_connected' => false,
            'status' => self::STATUS_ERROR,
            'error_message' => $message,
        ]);
    }

    /**
     * Disconnect
     */
    public function disconnect(): void
    {
        $this->update([
            'is_active' => false,
            'is_connected' => false,
            'status' => self::STATUS_INACTIVE,
            'credentials' => null,
        ]);
    }
}

