<?php

namespace Database\Seeders;

use App\Models\BusinessInfo;
use Illuminate\Database\Seeder;

class BusinessInfoSeeder extends Seeder
{
    public function run(): void
    {
        BusinessInfo::create([
            'name' => 'IF Irmak Fırın & Cafe',
            'phone' => '+90 (555) 123-4567',
            'email' => 'info@irmakfirin.com',
            'address' => 'Kadıköy, İstanbul, Türkiye',
            'tax_number' => '1234567890',
        ]);
    }
}
