<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Burgerler', 'icon' => '🍔', 'order' => 1],
            ['name' => 'Pizzalar', 'icon' => '🍕', 'order' => 2],
            ['name' => 'İçecekler', 'icon' => '🥤', 'order' => 3],
            ['name' => 'Tatlılar', 'icon' => '🍰', 'order' => 4],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'icon' => $category['icon'] ?? null,
                    'description' => $category['name'] . ' kategorisi',
                    'order' => $category['order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
