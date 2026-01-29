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
}

