<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BranchSetting extends Model
{
    protected $fillable = [
        'branch_id',
        'courier_enabled',
        'balance_tracking',
        'current_balance',
        'cash_balance_tracking',
        'current_cash_balance',
        'map_display',
        'nickname',
        // Pool settings
        'pool_enabled',
        'pool_wait_time',
        'pool_auto_assign',
        'pool_max_orders',
        'pool_priority_by_distance',
        'pool_notify_couriers',
    ];

    protected $casts = [
        'courier_enabled' => 'boolean',
        'balance_tracking' => 'boolean',
        'current_balance' => 'decimal:2',
        'cash_balance_tracking' => 'boolean',
        'current_cash_balance' => 'decimal:2',
        'map_display' => 'boolean',
        // Pool settings
        'pool_enabled' => 'boolean',
        'pool_wait_time' => 'integer',
        'pool_auto_assign' => 'boolean',
        'pool_max_orders' => 'integer',
        'pool_priority_by_distance' => 'boolean',
        'pool_notify_couriers' => 'boolean',
    ];

    public function branch()
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
                'pool_enabled' => false,
                'pool_wait_time' => 5,
                'pool_auto_assign' => false,
                'pool_max_orders' => 10,
                'pool_priority_by_distance' => true,
                'pool_notify_couriers' => true,
            ]
        );
    }
}
