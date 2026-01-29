<?php

namespace Tests\Unit\Services;

use App\Events\PoolOrderAdded;
use App\Events\PoolOrderAssigned;
use App\Models\Branch;
use App\Models\BranchSetting;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use App\Services\PoolService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PoolServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PoolService $poolService;
    protected Branch $branch;
    protected BranchSetting $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->poolService = new PoolService();

        // Create a branch
        $this->branch = Branch::factory()->create([
            'name' => 'Test Branch',
            'is_active' => true,
        ]);

        // Create branch settings with pool enabled
        $this->settings = BranchSetting::create([
            'branch_id' => $this->branch->id,
            'pool_enabled' => true,
            'pool_wait_time' => 5,
            'pool_auto_assign' => true,
            'pool_notify_couriers' => false,
        ]);
    }

    /** @test */
    public function it_can_add_order_to_pool(): void
    {
        Event::fake();

        $order = $this->createOrder();

        $this->poolService->addToPool($order);

        $order->refresh();

        $this->assertNotNull($order->pool_entered_at);
        Event::assertDispatched(PoolOrderAdded::class);
    }

    /** @test */
    public function it_can_accept_order_from_pool(): void
    {
        Event::fake();

        $order = $this->createOrder(['pool_entered_at' => now()]);
        $courier = $this->createCourier();

        $result = $this->poolService->acceptFromPool($order, $courier);

        $this->assertTrue($result['success']);
        $this->assertEquals('SUCCESS', $result['code']);

        $order->refresh();

        $this->assertEquals($courier->id, $order->courier_id);
        $this->assertNull($order->pool_entered_at);
        Event::assertDispatched(PoolOrderAssigned::class);
    }

    /** @test */
    public function it_prevents_accepting_order_not_in_pool(): void
    {
        $order = $this->createOrder(['pool_entered_at' => null]);
        $courier = $this->createCourier();

        $result = $this->poolService->acceptFromPool($order, $courier);

        $this->assertFalse($result['success']);
        $this->assertEquals('ORDER_TAKEN', $result['code']);
    }

    /** @test */
    public function it_prevents_courier_with_max_orders_from_accepting(): void
    {
        $order = $this->createOrder(['pool_entered_at' => now()]);
        $courier = $this->createCourier(['active_orders_count' => 5]);

        $result = $this->poolService->acceptFromPool($order, $courier);

        $this->assertFalse($result['success']);
        $this->assertEquals('LIMIT_REACHED', $result['code']);
    }

    /** @test */
    public function it_can_get_pool_orders(): void
    {
        // Create orders in pool
        $order1 = $this->createOrder([
            'pool_entered_at' => now()->subMinutes(10),
            'branch_id' => $this->branch->id,
        ]);
        $order2 = $this->createOrder([
            'pool_entered_at' => now()->subMinutes(5),
            'branch_id' => $this->branch->id,
        ]);

        // Create order not in pool
        $order3 = $this->createOrder([
            'pool_entered_at' => null,
            'branch_id' => $this->branch->id,
        ]);

        $poolOrders = $this->poolService->getPoolOrders($this->branch->id);

        $this->assertCount(2, $poolOrders);
        $this->assertTrue($poolOrders->contains($order1));
        $this->assertTrue($poolOrders->contains($order2));
        $this->assertFalse($poolOrders->contains($order3));
    }

    /** @test */
    public function pool_orders_are_sorted_by_oldest_first(): void
    {
        $order1 = $this->createOrder([
            'pool_entered_at' => now()->subMinutes(5),
            'branch_id' => $this->branch->id,
        ]);
        $order2 = $this->createOrder([
            'pool_entered_at' => now()->subMinutes(10),
            'branch_id' => $this->branch->id,
        ]);

        $poolOrders = $this->poolService->getPoolOrders($this->branch->id);

        $this->assertEquals($order2->id, $poolOrders->first()->id);
    }

    /** @test */
    public function it_can_get_pool_orders_count(): void
    {
        $this->createOrder([
            'pool_entered_at' => now(),
            'branch_id' => $this->branch->id,
        ]);
        $this->createOrder([
            'pool_entered_at' => now(),
            'branch_id' => $this->branch->id,
        ]);

        $count = $this->poolService->getPoolOrdersCount($this->branch->id);

        $this->assertEquals(2, $count);
    }

    /** @test */
    public function it_checks_if_pool_is_enabled(): void
    {
        $this->assertTrue($this->poolService->isPoolEnabled($this->branch->id));

        // Disable pool
        $this->settings->update(['pool_enabled' => false]);

        $this->assertFalse($this->poolService->isPoolEnabled($this->branch->id));
    }

    /** @test */
    public function it_can_get_pool_settings(): void
    {
        $settings = $this->poolService->getPoolSettings($this->branch->id);

        $this->assertNotNull($settings);
        $this->assertEquals($this->branch->id, $settings->branch_id);
        $this->assertTrue($settings->pool_enabled);
    }

    /** @test */
    public function it_can_get_pool_statistics(): void
    {
        // Create orders with different wait times
        $this->createOrder([
            'pool_entered_at' => now()->subMinutes(10),
            'branch_id' => $this->branch->id,
        ]);
        $this->createOrder([
            'pool_entered_at' => now()->subMinutes(2),
            'branch_id' => $this->branch->id,
        ]);

        $stats = $this->poolService->getPoolStats($this->branch->id);

        $this->assertEquals(2, $stats['total_pool']);
        $this->assertEquals(1, $stats['timeout_orders']); // Only first order exceeded 5 min wait
        $this->assertArrayHasKey('avg_wait_time', $stats);
        $this->assertArrayHasKey('available_couriers', $stats);
    }

    /**
     * Create a test order
     */
    protected function createOrder(array $attributes = []): Order
    {
        $customer = Customer::factory()->create();

        return Order::factory()->create(array_merge([
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'status' => 'ready',
        ], $attributes));
    }

    /**
     * Create a test courier
     */
    protected function createCourier(array $attributes = []): Courier
    {
        return Courier::factory()->create(array_merge([
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => 0,
        ], $attributes));
    }
}
