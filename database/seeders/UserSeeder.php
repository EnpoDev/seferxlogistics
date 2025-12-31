<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // User with both roles - will see panel selection
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'roles' => ['bayi', 'isletme'],
            ]
        );

        // User with only bayi role
        User::firstOrCreate(
            ['email' => 'bayi@example.com'],
            [
                'name' => 'Bayi User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'roles' => ['bayi'],
            ]
        );

        // User with only isletme role
        User::firstOrCreate(
            ['email' => 'mh@example.com'],
            [
                'name' => 'Muhammet Hüseyin Koçaş',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'roles' => ['isletme'],
            ]
        );
    }
}
