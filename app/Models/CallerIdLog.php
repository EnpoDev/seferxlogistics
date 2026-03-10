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

    // Scope methods
    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Accessor methods
    public function getCallerDisplayNameAttribute(): string
    {
        return $this->customer ? $this->customer->name : 'Kayıtsız Arama';
    }

    public function getFormattedPhoneAttribute(): string
    {
        $phone = preg_replace('/[^0-9]/', '', $this->phone);
        if (strlen($phone) === 10) {
            return sprintf('(%s) %s %s %s',
                substr($phone, 0, 3),
                substr($phone, 3, 3),
                substr($phone, 6, 2),
                substr($phone, 8, 2)
            );
        }
        return $this->phone;
    }
}
