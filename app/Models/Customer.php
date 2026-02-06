<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'lat',
        'lng',
        'notes',
        'total_orders',
        'total_spent',
        'last_order_at',
        'preferred_contact_method',
    ];

    protected $appends = [
        'customer_type',
        'loyalty_score',
        'average_order_value',
        'days_since_last_order',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'total_spent' => 'decimal:2',
        'last_order_at' => 'datetime',
    ];

    // Relationships
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    // Scopes
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('phone', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%");
        });
    }

    // Methods
    public function updateOrderStats(): void
    {
        $this->update([
            'total_orders' => $this->orders()->count(),
            'total_spent' => $this->orders()->where('status', 'delivered')->sum('total'),
            'last_order_at' => $this->orders()->latest()->first()?->created_at,
        ]);
    }

    public function getDefaultAddress(): ?CustomerAddress
    {
        return $this->addresses()->where('is_default', true)->first() 
            ?? $this->addresses()->latest()->first();
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

    /**
     * Get customer type based on order history
     * VIP: 20+ orders OR 5000+ TL spent
     * Yeni: 1-2 orders and first order within 30 days
     * Normal: everyone else
     */
    public function getCustomerTypeAttribute(): string
    {
        if ($this->total_orders >= 20 || $this->total_spent >= 5000) {
            return 'VIP';
        }

        if ($this->total_orders <= 2 && $this->last_order_at && $this->last_order_at->diffInDays(now()) <= 30) {
            return 'Yeni';
        }

        return 'Normal';
    }

    /**
     * Calculate loyalty score (0-100)
     * Order count: max 40 points (5 points per order)
     * Total spent: max 30 points (1 point per 100 TL)
     * Recency: max 30 points based on last order
     */
    public function getLoyaltyScoreAttribute(): int
    {
        // Order count score (max 40)
        $orderScore = min($this->total_orders * 5, 40);

        // Spending score (max 30)
        $spentScore = min($this->total_spent / 100, 30);

        // Recency score (max 30)
        $recencyScore = 0;
        if ($this->last_order_at) {
            $daysSince = $this->last_order_at->diffInDays(now());
            if ($daysSince <= 7) {
                $recencyScore = 30;
            } elseif ($daysSince <= 30) {
                $recencyScore = 20;
            } elseif ($daysSince <= 90) {
                $recencyScore = 10;
            }
        }

        return (int) round($orderScore + $spentScore + $recencyScore);
    }

    /**
     * Get average order value
     */
    public function getAverageOrderValueAttribute(): float
    {
        if ($this->total_orders <= 0) {
            return 0;
        }

        return round($this->total_spent / $this->total_orders, 2);
    }

    /**
     * Get days since last order
     */
    public function getDaysSinceLastOrderAttribute(): ?int
    {
        if (!$this->last_order_at) {
            return null;
        }

        return (int) $this->last_order_at->diffInDays(now());
    }
}

