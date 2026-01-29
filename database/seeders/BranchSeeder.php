<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        // İlk bayi kullanıcısını al veya yoksa ilk kullanıcıyı kullan
        $owner = User::whereJsonContains('roles', 'bayi')->first()
            ?? User::first();

        $ownerId = $owner?->id ?? 1;

        Branch::firstOrCreate(
            ['email' => 'kadikoy@example.com'],
            [
                'user_id' => $ownerId,
                'name' => 'Kadıköy Şubesi',
                'address' => 'Kadıköy, İstanbul',
                'phone' => '+90 (555) 123-4567',
                'lat' => 40.9903,
                'lng' => 29.0234,
                'is_main' => true,
                'is_active' => true,
            ]
        );

        Branch::firstOrCreate(
            ['email' => 'besiktas@example.com'],
            [
                'user_id' => $ownerId,
                'name' => 'Beşiktaş Şubesi',
                'address' => 'Beşiktaş, İstanbul',
                'phone' => '+90 (555) 765-4321',
                'lat' => 41.0422,
                'lng' => 29.0087,
                'is_main' => false,
                'is_active' => true,
            ]
        );
    }
}
