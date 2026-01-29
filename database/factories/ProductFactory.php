<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Lahmacun', 'Pide', 'Döner', 'Kebab', 'Pizza', 'Hamburger',
            'Tavuk Şiş', 'Adana Kebap', 'Urfa Kebap', 'İskender',
            'Karışık Izgara', 'Köfte', 'Tavuk Kanat', 'Patates Kızartması',
            'Ayran', 'Kola', 'Su', 'Çay', 'Türk Kahvesi', 'Baklava'
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 9999),
            'description' => fake()->optional(0.6)->sentence(),
            'price' => fake()->randomFloat(2, 20, 200),
            'is_active' => true,
            'in_stock' => true,
        ];
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the product is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'in_stock' => false,
        ]);
    }
}
