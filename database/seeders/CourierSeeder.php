<?php

namespace Database\Seeders;

use App\Models\Courier;
use Illuminate\Database\Seeder;

class CourierSeeder extends Seeder
{
    public function run(): void
    {
        $couriers = [
            ['name' => 'Mehmet Kaya', 'phone' => '+90 (555) 111-2233', 'status' => 'active'],
            ['name' => 'Ali Demir', 'phone' => '+90 (555) 222-3344', 'status' => 'busy'],
            ['name' => 'Can Yılmaz', 'phone' => '+90 (555) 333-4455', 'status' => 'active'],
            ['name' => 'Emre Şahin', 'phone' => '+90 (555) 444-5566', 'status' => 'active'],
        ];

        foreach ($couriers as $courier) {
            Courier::create($courier);
        }
    }
}
