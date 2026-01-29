<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchSetting extends Model
{
    protected $fillable = [
        'branch_id',
        'courier_enabled',
        'balance_tracking',
        'current_balance',
        'cash_balance_tracking',
        'current_cash_balance',
        // Commission settings
        'restaurant_commission_rate',
        'courier_fee_percentage',
        'map_display',
        'nickname',
        // Pool settings
        'pool_enabled',
        'pool_wait_time',
        'pool_auto_assign',
        'pool_ai_distribution',
        'ai_distribution_weights',
        'pool_max_orders',
        'pool_priority_by_distance',
        'pool_notify_couriers',
        // Courier settings
        'auto_assign_courier',
        'check_courier_shift',
        'max_delivery_time',
        // Customer notification settings
        'customer_sms_enabled',
        'customer_whatsapp_enabled',
        'notify_on_confirmed',
        'notify_on_preparing',
        'notify_on_ready',
        'notify_on_courier_assigned',
        'notify_on_picked_up',
        'notify_on_delivered',
        'notify_on_cancelled',
        // Geofencing settings
        'geofence_radius',
        'geofence_auto_arrival',
        'geofence_arrival_message',
    ];

    protected $casts = [
        'courier_enabled' => 'boolean',
        'balance_tracking' => 'boolean',
        'current_balance' => 'decimal:2',
        'cash_balance_tracking' => 'boolean',
        'current_cash_balance' => 'decimal:2',
        // Commission settings
        'restaurant_commission_rate' => 'decimal:2',
        'courier_fee_percentage' => 'decimal:2',
        'map_display' => 'boolean',
        // Pool settings
        'pool_enabled' => 'boolean',
        'pool_wait_time' => 'integer',
        'pool_auto_assign' => 'boolean',
        'pool_ai_distribution' => 'boolean',
        'ai_distribution_weights' => 'array',
        'pool_max_orders' => 'integer',
        'pool_priority_by_distance' => 'boolean',
        'pool_notify_couriers' => 'boolean',
        // Courier settings
        'auto_assign_courier' => 'boolean',
        'check_courier_shift' => 'boolean',
        'max_delivery_time' => 'integer',
        // Customer notification settings
        'customer_sms_enabled' => 'boolean',
        'customer_whatsapp_enabled' => 'boolean',
        'notify_on_confirmed' => 'boolean',
        'notify_on_preparing' => 'boolean',
        'notify_on_ready' => 'boolean',
        'notify_on_courier_assigned' => 'boolean',
        'notify_on_picked_up' => 'boolean',
        'notify_on_delivered' => 'boolean',
        'notify_on_cancelled' => 'boolean',
        // Geofencing settings
        'geofence_radius' => 'integer',
        'geofence_auto_arrival' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get or create settings for a branch
     */
    public static function getOrCreateForBranch(int $branchId): self
    {
        return self::firstOrCreate(
            ['branch_id' => $branchId],
            [
                // Commission defaults
                'restaurant_commission_rate' => 5.00,  // %5 komisyon
                'courier_fee_percentage' => 60.00,     // Kuryeye %60
                // Pool defaults
                'pool_enabled' => false,
                'pool_wait_time' => 5,
                'pool_auto_assign' => false,
                'pool_ai_distribution' => true,
                'pool_max_orders' => 10,
                'pool_priority_by_distance' => true,
                'pool_notify_couriers' => true,
            ]
        );
    }
}
