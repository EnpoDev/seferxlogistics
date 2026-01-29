<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourierNotification extends Model
{
    protected $fillable = [
        'courier_id',
        'type',
        'title',
        'message',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    // Types
    public const TYPE_ORDER_ASSIGNED = 'order_assigned';
    public const TYPE_ORDER_CANCELLED = 'order_cancelled';
    public const TYPE_POOL_ORDER = 'pool_order';
    public const TYPE_SYSTEM = 'system';

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeForCourier($query, int $courierId)
    {
        return $query->where('courier_id', $courierId);
    }
}
