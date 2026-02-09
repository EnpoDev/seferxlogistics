<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallerIdLog extends Model
{
    protected $fillable = [
        'branch_id',
        'customer_id',
        'phone',
        'device_id',
        'line',
        'device_datetime',
        'str0',
        'str1',
        'ip',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
