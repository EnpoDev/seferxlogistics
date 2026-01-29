<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Dönerler', 'Kebaplar', 'Pizzalar', 'Hamburgerler',
            'İçecekler', 'Tatlılar', 'Salatalar', 'Çorbalar',
            'Pideler', 'Lahmacunlar', 'Izgara Çeşitleri'
        ]);

        return [
            'name' => $name . ' ' . fake()->unique()->numberBetween(1, 999),
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 9999),
            'is_active' => true,
            'order' => fake()->numberBetween(1, 100),
        ];
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
