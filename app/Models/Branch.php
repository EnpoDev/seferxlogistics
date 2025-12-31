<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'lat',
        'lng',
        'is_main',
        'is_active',
        'parent_id',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'is_main' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function parent()
    {
        return $this->belongsTo(Branch::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Branch::class, 'parent_id');
    }

    public function settings()
    {
        return $this->hasOne(BranchSetting::class);
    }

    public function pricingPolicies()
    {
        return $this->hasMany(PricingPolicy::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
