<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallLog extends Model
{
    protected $fillable = [
        'order_id',
        'caller_type',
        'from_number',
        'to_number',
        'proxy_number',
        'external_call_id',
        'started_at',
        'ended_at',
        'duration',
        'status',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'metadata' => 'array',
    ];

    public const CALLER_CUSTOMER = 'customer';
    public const CALLER_COURIER = 'courier';

    public const STATUS_INITIATED = 'initiated';
    public const STATUS_RINGING = 'ringing';
    public const STATUS_ANSWERED = 'answered';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_MISSED = 'missed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_BUSY = 'busy';

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function getStatusLabel(): string
    {
        return __('statuses.call.' . $this->status, [], 'tr') ?? $this->status;
    }

    public function getDurationFormatted(): string
    {
        if (!$this->duration) {
            return '-';
        }

        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_ANSWERED,
        ]);
    }

    public function isMissed(): bool
    {
        return $this->status === self::STATUS_MISSED;
    }
}
