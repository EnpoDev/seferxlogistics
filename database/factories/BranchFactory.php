<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->bayi(),
            'name' => fake()->company() . ' Şubesi',
            'phone' => '0212' . fake()->numerify('#######'),
            'address' => fake()->streetAddress() . ', ' . fake()->city(),
            'lat' => 41.0 + (fake()->randomFloat(4, 0, 0.1)),
            'lng' => 29.0 + (fake()->randomFloat(4, 0, 0.1)),
            'is_active' => true,
            'is_main' => false,
        ];
    }

    /**
     * Indicate that the branch is the main branch.
     */
    public function main(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_main' => true,
        ]);
    }

    /**
     * Indicate that the branch is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
