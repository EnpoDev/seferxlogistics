<?php

namespace Database\Factories;

use App\Models\Courier;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Courier>
 */
class CourierFactory extends Factory
{
    protected $model = Courier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => '0532' . fake()->numerify('#######'),
            'email' => fake()->unique()->safeEmail(),
            'tc_no' => fake()->numerify('###########'),
            'vehicle_plate' => strtoupper(fake()->numerify('34 ?? ###')),
            'status' => Courier::STATUS_AVAILABLE,
            'lat' => 41.0 + (fake()->randomFloat(4, 0, 0.1)),
            'lng' => 29.0 + (fake()->randomFloat(4, 0, 0.1)),
            'active_orders_count' => 0,
            'total_deliveries' => fake()->numberBetween(0, 500),
            'cash_balance' => fake()->randomFloat(2, 0, 1000),
            'average_delivery_time' => fake()->randomFloat(2, 15, 45),
            'notification_enabled' => true,
            'is_app_enabled' => true,
        ];
    }

    /**
     * Indicate that the courier is available.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Courier::STATUS_AVAILABLE,
        ]);
    }

    /**
     * Indicate that the courier is busy.
     */
    public function busy(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Courier::STATUS_BUSY,
            'active_orders_count' => fake()->numberBetween(1, 3),
        ]);
    }

    /**
     * Indicate that the courier is offline.
     */
    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Courier::STATUS_OFFLINE,
        ]);
    }

    /**
     * Indicate that the courier is on break.
     */
    public function onBreak(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Courier::STATUS_ON_BREAK,
        ]);
    }
}
