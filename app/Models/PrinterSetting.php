<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrinterSetting extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'type', // kitchen, receipt, label
        'connection_type', // usb, network, bluetooth
        'ip_address',
        'port',
        'model',
        'is_active',
        'auto_print',
        'copies',
        'print_on_new_order',
        'print_on_status_change',
        'paper_width', // 58mm, 80mm
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_print' => 'boolean',
        'print_on_new_order' => 'boolean',
        'print_on_status_change' => 'boolean',
        'copies' => 'integer',
        'port' => 'integer',
        'settings' => 'array',
    ];

    const TYPE_KITCHEN = 'kitchen';
    const TYPE_RECEIPT = 'receipt';
    const TYPE_LABEL = 'label';

    const CONNECTION_USB = 'usb';
    const CONNECTION_NETWORK = 'network';
    const CONNECTION_BLUETOOTH = 'bluetooth';

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeKitchen($query)
    {
        return $query->where('type', self::TYPE_KITCHEN);
    }

    public function scopeReceipt($query)
    {
        return $query->where('type', self::TYPE_RECEIPT);
    }

    // Methods
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_KITCHEN => 'Mutfak Yazıcısı',
            self::TYPE_RECEIPT => 'Fiş Yazıcısı',
            self::TYPE_LABEL => 'Etiket Yazıcısı',
            default => $this->type,
        };
    }

    public function getConnectionLabel(): string
    {
        return match ($this->connection_type) {
            self::CONNECTION_USB => 'USB',
            self::CONNECTION_NETWORK => 'Ağ (Ethernet)',
            self::CONNECTION_BLUETOOTH => 'Bluetooth',
            default => $this->connection_type,
        };
    }

    public function getConnectionString(): string
    {
        if ($this->connection_type === self::CONNECTION_NETWORK) {
            return $this->ip_address . ($this->port ? ':' . $this->port : '');
        }
        
        return $this->connection_type ?? '';
    }

    public function testConnection(): bool
    {
        // In production, implement actual connection test
        // For now, return true if settings are configured
        if ($this->connection_type === self::CONNECTION_NETWORK) {
            return !empty($this->ip_address);
        }
        
        return true;
    }
}

