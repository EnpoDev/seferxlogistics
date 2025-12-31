<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50, 500);
        $deliveryFee = fake()->randomFloat(2, 5, 30);

        return [
            'order_number' => 'ORD-' . str_pad(fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'customer_name' => fake()->name(),
            'customer_phone' => '0532' . fake()->numerify('#######'),
            'customer_address' => fake()->streetAddress() . ' ' . fake()->city() . '/' . fake()->state(),
            'lat' => 41.0 + (fake()->randomFloat(4, 0, 0.1)),
            'lng' => 29.0 + (fake()->randomFloat(4, 0, 0.1)),
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'total' => $subtotal + $deliveryFee,
            'payment_method' => fake()->randomElement([Order::PAYMENT_CASH, Order::PAYMENT_CARD, Order::PAYMENT_ONLINE]),
            'is_paid' => fake()->boolean(70),
            'status' => Order::STATUS_PENDING,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_PENDING,
        ]);
    }

    /**
     * Indicate that the order is preparing.
     */
    public function preparing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_PREPARING,
            'accepted_at' => now(),
        ]);
    }

    /**
     * Indicate that the order is ready.
     */
    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_READY,
            'accepted_at' => now()->subMinutes(10),
            'prepared_at' => now(),
        ]);
    }

    /**
     * Indicate that the order is on delivery.
     */
    public function onDelivery(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_ON_DELIVERY,
            'accepted_at' => now()->subMinutes(20),
            'prepared_at' => now()->subMinutes(10),
            'picked_up_at' => now(),
        ]);
    }

    /**
     * Indicate that the order is delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_DELIVERED,
            'accepted_at' => now()->subMinutes(40),
            'prepared_at' => now()->subMinutes(30),
            'picked_up_at' => now()->subMinutes(20),
            'delivered_at' => now(),
            'is_paid' => true,
        ]);
    }

    /**
     * Indicate that the order is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Order::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancel_reason' => fake()->sentence(),
        ]);
    }
}
