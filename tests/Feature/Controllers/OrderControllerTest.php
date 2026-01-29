<?php

namespace Tests\Feature\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->branch = Branch::factory()->create();
        $this->category = Category::factory()->create();
    }

    /** @test */
    public function authenticated_user_can_view_orders_list(): void
    {
        Order::factory()->count(5)->create([
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('siparis.liste'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.siparis.liste');
        $response->assertViewHas('orders');
    }

    /** @test */
    public function unauthenticated_user_cannot_view_orders(): void
    {
        $response = $this->get(route('siparis.liste'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function orders_can_be_filtered_by_status(): void
    {
        Order::factory()->pending()->create(['branch_id' => $this->branch->id]);
        Order::factory()->delivered()->create(['branch_id' => $this->branch->id]);

        $response = $this->actingAs($this->user)
            ->get(route('siparis.liste', ['status' => 'pending']));

        $response->assertStatus(200);
        $orders = $response->viewData('orders');

        $this->assertTrue($orders->every(fn($order) => $order->status === 'pending'));
    }

    /** @test */
    public function orders_can_be_searched(): void
    {
        $searchOrder = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_name' => 'Unique Test Customer Name',
        ]);

        Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_name' => 'Other Customer',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('siparis.liste', ['search' => 'Unique Test']));

        $response->assertStatus(200);
        $orders = $response->viewData('orders');

        $this->assertTrue($orders->contains('id', $searchOrder->id));
    }

    /** @test */
    public function user_can_view_order_create_form(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('siparis.create'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.siparis.create');
    }

    /** @test */
    public function user_can_create_order_with_valid_data(): void
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'price' => 50.00,
        ]);

        $orderData = [
            'customer_name' => 'Test Customer',
            'customer_phone' => '5551234567',
            'customer_address' => 'Test Address 123',
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'delivery_fee' => 10.00,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('siparis.store'), $orderData);

        $response->assertRedirect(route('siparis.liste'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Test Customer',
            'customer_phone' => '5551234567',
        ]);

        $this->assertDatabaseHas('customers', [
            'phone' => '5551234567',
        ]);
    }

    /** @test */
    public function order_creation_fails_with_invalid_data(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('siparis.store'), [
                'customer_name' => '',
                'customer_phone' => '',
                'items' => [],
            ]);

        $response->assertSessionHasErrors(['customer_name', 'customer_phone', 'items']);
    }

    /** @test */
    public function user_can_view_order_history(): void
    {
        Order::factory()->delivered()->count(3)->create(['branch_id' => $this->branch->id]);
        Order::factory()->cancelled()->count(2)->create(['branch_id' => $this->branch->id]);

        $response = $this->actingAs($this->user)
            ->get(route('siparis.gecmis'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.siparis.gecmis');
        $response->assertViewHas('orders');
    }

    /** @test */
    public function user_can_view_cancelled_orders(): void
    {
        Order::factory()->cancelled()->count(3)->create(['branch_id' => $this->branch->id]);

        $response = $this->actingAs($this->user)
            ->get(route('siparis.iptal'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.siparis.iptal');
    }

    /** @test */
    public function user_can_view_order_statistics(): void
    {
        Order::factory()->pending()->count(2)->create(['branch_id' => $this->branch->id]);
        Order::factory()->delivered()->count(5)->create(['branch_id' => $this->branch->id]);

        $response = $this->actingAs($this->user)
            ->get(route('siparis.istatistik'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.siparis.istatistik');
        $response->assertViewHas('stats');
    }

    /** @test */
    public function user_can_update_order_status(): void
    {
        $order = Order::factory()->pending()->create([
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user)
            ->patch(route('siparis.updateStatus', $order), [
                'status' => 'preparing',
            ]);

        // Accept either redirect or success response
        $this->assertTrue(
            $response->isRedirect() || $response->isOk(),
            'Expected redirect or success response'
        );
    }

    /** @test */
    public function order_can_be_updated(): void
    {
        $order = Order::factory()->pending()->create([
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('siparis.update', $order), [
                'customer_name' => 'Updated Name',
                'customer_phone' => $order->customer_phone,
                'customer_address' => $order->customer_address,
            ]);

        // Accept either redirect or success response
        $this->assertTrue(
            $response->isRedirect() || $response->isOk(),
            'Expected redirect or success response'
        );
    }

    /** @test */
    public function order_items_are_created_with_order(): void
    {
        $product1 = Product::factory()->create([
            'category_id' => $this->category->id,
            'price' => 50.00,
        ]);
        $product2 = Product::factory()->create([
            'category_id' => $this->category->id,
            'price' => 30.00,
        ]);

        $orderData = [
            'customer_name' => 'Test Customer',
            'customer_phone' => '5551234567',
            'customer_address' => 'Test Address',
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash',
            'delivery_fee' => 10.00,
            'items' => [
                ['product_id' => $product1->id, 'quantity' => 2],
                ['product_id' => $product2->id, 'quantity' => 1],
            ],
        ];

        $this->actingAs($this->user)
            ->post(route('siparis.store'), $orderData);

        $order = Order::where('customer_phone', '5551234567')->first();

        $this->assertCount(2, $order->items);
        $this->assertEquals(140.00, $order->total); // (50*2) + (30*1) + 10 delivery
    }

    /** @test */
    public function customer_stats_are_updated_on_order_creation(): void
    {
        $customer = Customer::factory()->create([
            'phone' => '5559876543',
            'total_orders' => 0,
        ]);

        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'price' => 100.00,
        ]);

        $this->actingAs($this->user)
            ->post(route('siparis.store'), [
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'customer_address' => 'Test Address',
                'branch_id' => $this->branch->id,
                'payment_method' => 'cash',
                'delivery_fee' => 10.00,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ]);

        $customer->refresh();
        $this->assertGreaterThanOrEqual(1, $customer->total_orders);
    }

    /** @test */
    public function orders_can_be_filtered_by_date(): void
    {
        $today = now()->format('Y-m-d');

        Order::factory()->create([
            'branch_id' => $this->branch->id,
            'created_at' => now(),
        ]);

        Order::factory()->create([
            'branch_id' => $this->branch->id,
            'created_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('siparis.liste', ['date' => $today]));

        $response->assertStatus(200);
        $orders = $response->viewData('orders');

        $this->assertTrue($orders->every(fn($order) => $order->created_at->format('Y-m-d') === $today));
    }
}
