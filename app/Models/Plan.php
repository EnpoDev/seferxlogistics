<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'billing_period', // monthly, yearly
        'features',
        'max_users',
        'max_orders',
        'max_branches',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'max_users' => 'integer',
        'max_orders' => 'integer',
        'max_branches' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeMonthly($query)
    {
        return $query->where('billing_period', 'monthly');
    }

    public function scopeYearly($query)
    {
        return $query->where('billing_period', 'yearly');
    }

    // Methods
    public function getFormattedPrice(): string
    {
        return '₺' . number_format($this->price, 2, ',', '.');
    }

    public function getPeriodLabel(): string
    {
        return match ($this->billing_period) {
            'monthly' => 'Aylık',
            'yearly' => 'Yıllık',
            default => $this->billing_period,
        };
    }

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }
}

