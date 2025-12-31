<?php

namespace Database\Seeders;

use App\Models\BusinessInfo;
use Illuminate\Database\Seeder;

class BusinessInfoSeeder extends Seeder
{
    public function run(): void
    {
        BusinessInfo::firstOrCreate(
            ['email' => 'info@irmakfirin.com'],
            [
                'name' => 'IF Irmak Fırın & Cafe',
                'phone' => '+90 (555) 123-4567',
                'address' => 'Kadıköy, İstanbul, Türkiye',
                'tax_number' => '1234567890',
            ]
        );
    }
}
