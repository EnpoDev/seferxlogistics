<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashRegisterSetting extends Model
{
    protected $fillable = [
        'user_id',
        'is_enabled',
        'model',
        'connection_type', // serial, ethernet, usb
        'port', // COM port or IP address
        'baud_rate',
        'default_vat_rate',
        'auto_send_orders',
        'settings',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'auto_send_orders' => 'boolean',
        'baud_rate' => 'integer',
        'default_vat_rate' => 'integer',
        'settings' => 'array',
    ];

    const MODEL_HUGIN = 'hugin';
    const MODEL_OLIVETTI = 'olivetti';
    const MODEL_INGENICO = 'ingenico';
    const MODEL_CUSTOM = 'custom';

    const CONNECTION_SERIAL = 'serial';
    const CONNECTION_ETHERNET = 'ethernet';
    const CONNECTION_USB = 'usb';

    const VAT_RATES = [1, 8, 10, 18, 20];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Methods
    public static function getOrCreateForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'is_enabled' => false,
                'model' => null,
                'connection_type' => self::CONNECTION_SERIAL,
                'default_vat_rate' => 20,
                'auto_send_orders' => false,
            ]
        );
    }

    public function getModelLabel(): string
    {
        return match ($this->model) {
            self::MODEL_HUGIN => 'Hugin',
            self::MODEL_OLIVETTI => 'Olivetti',
            self::MODEL_INGENICO => 'Ingenico',
            self::MODEL_CUSTOM => 'Diğer',
            default => 'Seçilmedi',
        };
    }

    public function getConnectionLabel(): string
    {
        return match ($this->connection_type) {
            self::CONNECTION_SERIAL => 'Seri Port (COM)',
            self::CONNECTION_ETHERNET => 'Ethernet',
            self::CONNECTION_USB => 'USB',
            default => $this->connection_type,
        };
    }

    public function testConnection(): bool
    {
        if (!$this->is_enabled || !$this->model || !$this->port) {
            return false;
        }

        // In production, implement actual connection test
        // For now, simulate success if configured
        return true;
    }

    public function sendOrder(Order $order): bool
    {
        if (!$this->is_enabled) {
            return false;
        }

        // In production, implement actual order sending to cash register
        // This would format the order data according to the model's protocol
        // and send it via the configured connection

        return true;
    }
}

