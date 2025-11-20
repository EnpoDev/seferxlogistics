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
            ['name' => 'Burgerler', 'order' => 1],
            ['name' => 'Pizzalar', 'order' => 2],
            ['name' => 'İçecekler', 'order' => 3],
            ['name' => 'Tatlılar', 'order' => 4],
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'description' => $category['name'] . ' kategorisi',
                'order' => $category['order'],
                'is_active' => true,
            ]);
        }
    }
}
