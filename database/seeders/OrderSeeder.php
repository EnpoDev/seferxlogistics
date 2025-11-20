<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        // Active order - preparing
        $order1 = Order::create([
            'order_number' => 'ORD-' . str_pad(1, 6, '0', STR_PAD_LEFT),
            'user_id' => 1,
            'courier_id' => null,
            'branch_id' => 1,
            'customer_name' => 'Ayşe Demir',
            'customer_phone' => '+90 (555) 987-6543',
            'customer_address' => 'Beşiktaş, İstanbul',
            'lat' => 41.0422,
            'lng' => 29.0087,
            'subtotal' => 89.50,
            'delivery_fee' => 10.00,
            'total' => 99.50,
            'status' => 'preparing',
            'accepted_at' => now()->subMinutes(15),
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => 1,
            'product_name' => 'Cheeseburger',
            'price' => 45.00,
            'quantity' => 1,
            'total' => 45.00,
        ]);

        OrderItem::create([
            'order_id' => $order1->id,
            'product_id' => 4,
            'product_name' => 'Margherita Pizza',
            'price' => 65.00,
            'quantity' => 1,
            'total' => 65.00,
        ]);

        // Active order - on delivery
        $order2 = Order::create([
            'order_number' => 'ORD-' . str_pad(2, 6, '0', STR_PAD_LEFT),
            'user_id' => 1,
            'courier_id' => 1,
            'branch_id' => 1,
            'customer_name' => 'Ahmet Yılmaz',
            'customer_phone' => '+90 (555) 123-4567',
            'customer_address' => 'Kadıköy, İstanbul',
            'lat' => 40.9903,
            'lng' => 29.0234,
            'subtotal' => 125.00,
            'delivery_fee' => 10.00,
            'total' => 135.00,
            'status' => 'on_delivery',
            'accepted_at' => now()->subMinutes(45),
            'prepared_at' => now()->subMinutes(30),
            'picked_up_at' => now()->subMinutes(15),
        ]);

        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => 2,
            'product_name' => 'Bacon Burger',
            'price' => 55.00,
            'quantity' => 2,
            'total' => 110.00,
        ]);

        OrderItem::create([
            'order_id' => $order2->id,
            'product_id' => 6,
            'product_name' => 'Kola',
            'price' => 10.00,
            'quantity' => 2,
            'total' => 20.00,
        ]);

        // Delivered order
        $order3 = Order::create([
            'order_number' => 'ORD-' . str_pad(3, 6, '0', STR_PAD_LEFT),
            'user_id' => 1,
            'courier_id' => 2,
            'branch_id' => 1,
            'customer_name' => 'Zeynep Kaya',
            'customer_phone' => '+90 (555) 999-8888',
            'customer_address' => 'Şişli, İstanbul',
            'lat' => 41.0605,
            'lng' => 28.9887,
            'subtotal' => 75.00,
            'delivery_fee' => 15.00,
            'total' => 90.00,
            'status' => 'delivered',
            'accepted_at' => now()->subHours(2),
            'prepared_at' => now()->subHours(1)->subMinutes(45),
            'picked_up_at' => now()->subHours(1)->subMinutes(30),
            'delivered_at' => now()->subHours(1),
        ]);

        OrderItem::create([
            'order_id' => $order3->id,
            'product_id' => 5,
            'product_name' => 'Pepperoni Pizza',
            'price' => 75.00,
            'quantity' => 1,
            'total' => 75.00,
        ]);
    }
}
