<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::create([
            'name' => 'Kadıköy Şubesi',
            'address' => 'Kadıköy, İstanbul',
            'phone' => '+90 (555) 123-4567',
            'email' => 'kadikoy@example.com',
            'lat' => 40.9903,
            'lng' => 29.0234,
            'is_main' => true,
            'is_active' => true,
        ]);

        Branch::create([
            'name' => 'Beşiktaş Şubesi',
            'address' => 'Beşiktaş, İstanbul',
            'phone' => '+90 (555) 765-4321',
            'email' => 'besiktas@example.com',
            'lat' => 41.0422,
            'lng' => 29.0087,
            'is_main' => false,
            'is_active' => true,
        ]);
    }
}
