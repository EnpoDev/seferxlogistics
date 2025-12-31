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
    ];

    protected $casts = [
        'courier_enabled' => 'boolean',
        'balance_tracking' => 'boolean',
        'current_balance' => 'decimal:2',
        'cash_balance_tracking' => 'boolean',
        'current_cash_balance' => 'decimal:2',
        'map_display' => 'boolean',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
