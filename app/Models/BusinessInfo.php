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
        'default_break_duration',
        'default_break_parts',
        'auto_assign_shifts',
    ];

    protected $casts = [
        'default_shifts' => 'array',
        'default_break_duration' => 'integer',
        'default_break_parts' => 'integer',
        'auto_assign_shifts' => 'boolean',
    ];
}
