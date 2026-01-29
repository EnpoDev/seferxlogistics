<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '05' . fake()->randomElement(['32', '33', '35', '42', '43', '44', '45', '46']) . fake()->numerify('#######'),
            'email' => fake()->optional(0.7)->safeEmail(),
            'address' => fake()->streetAddress() . ', ' . fake()->city(),
            'lat' => 41.0 + (fake()->randomFloat(4, 0, 0.1)),
            'lng' => 29.0 + (fake()->randomFloat(4, 0, 0.1)),
            'total_orders' => 0,
            'total_spent' => 0,
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate that the customer has many orders.
     */
    public function withOrders(int $count = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'total_orders' => $count,
            'total_spent' => fake()->randomFloat(2, $count * 50, $count * 200),
            'last_order_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }
}
