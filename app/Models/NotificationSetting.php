<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSetting extends Model
{
    protected $fillable = [
        'user_id',
        'new_order_notification',
        'order_status_notification',
        'order_cancelled_notification',
        'email_daily_summary',
        'email_weekly_report',
        'email_new_order',
        'push_enabled',
        'push_new_order',
        'push_order_status',
        'sms_enabled',
        'sms_new_order',
        'sound_enabled',
        'notification_sound',
    ];

    protected $casts = [
        'new_order_notification' => 'boolean',
        'order_status_notification' => 'boolean',
        'order_cancelled_notification' => 'boolean',
        'email_daily_summary' => 'boolean',
        'email_weekly_report' => 'boolean',
        'email_new_order' => 'boolean',
        'push_enabled' => 'boolean',
        'push_new_order' => 'boolean',
        'push_order_status' => 'boolean',
        'sms_enabled' => 'boolean',
        'sms_new_order' => 'boolean',
        'sound_enabled' => 'boolean',
    ];

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
                'new_order_notification' => true,
                'order_status_notification' => true,
                'order_cancelled_notification' => true,
                'email_daily_summary' => true,
                'push_enabled' => true,
                'push_new_order' => true,
                'push_order_status' => true,
                'sound_enabled' => true,
            ]
        );
    }

    public function shouldNotifyNewOrder(): bool
    {
        return $this->new_order_notification && ($this->push_enabled || $this->email_new_order);
    }

    public function shouldNotifyOrderStatus(): bool
    {
        return $this->order_status_notification && ($this->push_enabled || $this->push_order_status);
    }
}

