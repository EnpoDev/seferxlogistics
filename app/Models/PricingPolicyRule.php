<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingPolicyRule extends Model
{
    protected $fillable = [
        'pricing_policy_id',
        'min_value',
        'max_value',
        'price',
        'percentage',
        'order',
    ];

    protected $casts = [
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
        'price' => 'decimal:2',
        'percentage' => 'decimal:2',
    ];

    public function pricingPolicy()
    {
        return $this->belongsTo(PricingPolicy::class);
    }
}
