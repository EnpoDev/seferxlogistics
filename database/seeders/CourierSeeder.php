<?php

namespace Database\Seeders;

use App\Models\Courier;
use Illuminate\Database\Seeder;

class CourierSeeder extends Seeder
{
    public function run(): void
    {
        $couriers = [
            ['name' => 'Mehmet Kaya', 'phone' => '5551112233', 'status' => 'available'],
            ['name' => 'Ali Demir', 'phone' => '5552223344', 'status' => 'busy'],
            ['name' => 'Can Yılmaz', 'phone' => '5553334455', 'status' => 'available'],
            ['name' => 'Emre Şahin', 'phone' => '5554445566', 'status' => 'available'],
        ];

        foreach ($couriers as $courier) {
            Courier::firstOrCreate(
                ['phone' => $courier['phone']],
                $courier
            );
        }
    }
}
