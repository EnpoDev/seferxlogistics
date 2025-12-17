<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Restaurant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'banner_image',
        'address',
        'phone',
        'lat',
        'lng',
        'is_featured',
        'is_active',
        'rating',
        'min_order_amount',
        'delivery_fee',
        'max_delivery_time',
        'working_hours',
        'order',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'rating' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'working_hours' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($restaurant) {
            if (empty($restaurant->slug)) {
                $restaurant->slug = Str::slug($restaurant->name);
            }
        });
    }

    // Relationships
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
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

    // Methods
    public function isOpen(): bool
    {
        if (!$this->working_hours) {
            return true;
        }

        $now = now();
        $dayName = strtolower($now->format('l'));
        
        if (!isset($this->working_hours[$dayName])) {
            return false;
        }

        $hours = $this->working_hours[$dayName];
        
        if (!$hours['is_open']) {
            return false;
        }

        $currentTime = $now->format('H:i');
        return $currentTime >= $hours['open'] && $currentTime <= $hours['close'];
    }

    public function getActiveProductsCount(): int
    {
        return $this->products()->where('is_active', true)->count();
    }

    public function calculateAverageRating(): float
    {
        // This can be expanded to calculate from reviews
        return (float) $this->rating;
    }
}

