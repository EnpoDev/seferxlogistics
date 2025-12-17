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
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'roles' => ['bayi', 'isletme'],
        ]);

        // User with only bayi role
        User::create([
            'name' => 'Bayi User',
            'email' => 'bayi@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'roles' => ['bayi'],
        ]);

        // User with only isletme role
        User::create([
            'name' => 'Muhammet Hüseyin Koçaş',
            'email' => 'mh@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'roles' => ['isletme'],
        ]);
    }
}
