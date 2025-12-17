<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessInfo extends Model
{
    protected $table = 'business_info';
    
    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'tax_number',
        'logo',
        'default_shifts',
        'auto_assign_shifts',
    ];

    protected $casts = [
        'default_shifts' => 'array',
        'auto_assign_shifts' => 'boolean',
    ];
}
