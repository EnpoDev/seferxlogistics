<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    protected $fillable = [
        'customer_id',
        'title',
        'address',
        'lat',
        'lng',
        'building_no',
        'floor',
        'apartment_no',
        'directions',
        'is_default',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'is_default' => 'boolean',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Methods
    public function getFullAddressAttribute(): string
    {
        $parts = [$this->address];
        
        if ($this->building_no) {
            $parts[] = "No: {$this->building_no}";
        }
        if ($this->floor) {
            $parts[] = "Kat: {$this->floor}";
        }
        if ($this->apartment_no) {
            $parts[] = "Daire: {$this->apartment_no}";
        }
        
        return implode(', ', $parts);
    }

    public function setAsDefault(): void
    {
        // Remove default from other addresses
        $this->customer->addresses()
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);
        
        $this->update(['is_default' => true]);
    }
}

