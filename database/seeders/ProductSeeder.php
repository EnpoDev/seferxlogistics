<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Cheeseburger', 'category_id' => 1, 'price' => 45.00],
            ['name' => 'Bacon Burger', 'category_id' => 1, 'price' => 55.00],
            ['name' => 'Veggie Burger', 'category_id' => 1, 'price' => 40.00],
            ['name' => 'Margherita Pizza', 'category_id' => 2, 'price' => 65.00],
            ['name' => 'Pepperoni Pizza', 'category_id' => 2, 'price' => 75.00],
            ['name' => 'Kola', 'category_id' => 3, 'price' => 10.00],
            ['name' => 'Fanta', 'category_id' => 3, 'price' => 10.00],
            ['name' => 'Su', 'category_id' => 3, 'price' => 5.00],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(
                ['slug' => Str::slug($product['name'])],
                [
                    'category_id' => $product['category_id'],
                    'name' => $product['name'],
                    'description' => $product['name'] . ' açıklaması',
                    'price' => $product['price'],
                    'is_active' => true,
                    'in_stock' => true,
                ]
            );
        }
    }
}
