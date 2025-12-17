<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'restaurant_id',
        'name',
        'slug',
        'description',
        'options',
        'price',
        'discounted_price',
        'image',
        'is_active',
        'in_stock',
        'preparation_time',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'is_active' => 'boolean',
        'in_stock' => 'boolean',
        'options' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name) . '-' . Str::random(5);
            }
        });
    }

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('in_stock', true);
    }

    public function scopeAvailable($query)
    {
        return $query->active()->inStock();
    }

    // Methods
    public function getCurrentPrice(): float
    {
        return $this->discounted_price ?? $this->price;
    }

    public function hasDiscount(): bool
    {
        return $this->discounted_price !== null && $this->discounted_price < $this->price;
    }

    public function getDiscountPercentage(): ?int
    {
        if (!$this->hasDiscount()) {
            return null;
        }
        
        return (int) round((($this->price - $this->discounted_price) / $this->price) * 100);
    }
}
