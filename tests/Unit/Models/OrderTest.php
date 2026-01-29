<?php

namespace Tests\Unit\Models;

use App\Models\Branch;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branch;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->branch = Branch::factory()->create();
        $this->customer = Customer::factory()->create();
    }

    /** @test */
    public function it_generates_order_number(): void
    {
        $orderNumber = Order::generateOrderNumber();

        $this->assertStringStartsWith('ORD-', $orderNumber);
        $this->assertEquals(10, strlen($orderNumber)); // ORD-000001
    }

    /** @test */
    public function it_generates_unique_tracking_token(): void
    {
        $token1 = Order::generateTrackingToken();
        $token2 = Order::generateTrackingToken();

        $this->assertNotEquals($token1, $token2);
        $this->assertEquals(16, strlen($token1));
    }

    /** @test */
    public function it_automatically_generates_tracking_token_on_create(): void
    {
        $order = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
        ]);

        $this->assertNotNull($order->tracking_token);
    }

    /** @test */
    public function it_can_find_order_by_tracking_token(): void
    {
        $order = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
        ]);

        $found = Order::findByTrackingToken($order->tracking_token);

        $this->assertNotNull($found);
        $this->assertEquals($order->id, $found->id);
    }

    /** @test */
    public function it_returns_null_for_invalid_tracking_token(): void
    {
        $found = Order::findByTrackingToken('INVALID_TOKEN');

        $this->assertNull($found);
    }

    /** @test */
    public function it_can_enter_pool(): void
    {
        $order = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => Order::STATUS_READY,
            'pool_entered_at' => null,
        ]);

        $order->enterPool();

        $this->assertNotNull($order->fresh()->pool_entered_at);
    }

    /** @test */
    public function it_can_leave_pool(): void
    {
        $order = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => Order::STATUS_READY,
            'pool_entered_at' => now()->subMinutes(5),
        ]);

        $order->leavePool();

        $this->assertNull($order->fresh()->pool_entered_at);
    }

    /** @test */
    public function it_correctly_identifies_order_in_pool(): void
    {
        $orderInPool = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => Order::STATUS_READY,
            'courier_id' => null,
            'pool_entered_at' => now(),
        ]);

        $orderNotInPool = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => Order::STATUS_PENDING,
            'pool_entered_at' => null,
        ]);

        $this->assertTrue($orderInPool->isInPool());
        $this->assertFalse($orderNotInPool->isInPool());
    }

    /** @test */
    public function it_calculates_pool_waiting_time(): void
    {
        $order = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'pool_entered_at' => now()->subMinutes(5),
        ]);

        $this->assertGreaterThanOrEqual(5, $order->poolWaitingMinutes());
        $this->assertGreaterThanOrEqual(300, $order->poolWaitingSeconds());
    }

    /** @test */
    public function it_returns_null_for_waiting_time_when_not_in_pool(): void
    {
        $order = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'pool_entered_at' => null,
        ]);

        $this->assertNull($order->poolWaitingMinutes());
        $this->assertNull($order->poolWaitingSeconds());
    }

    /** @test */
    public function it_can_assign_courier(): void
    {
        $courier = Courier::factory()->create();
        $order = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'courier_id' => null,
            'pool_entered_at' => now(),
        ]);

        $order->assignCourier($courier);

        $order->refresh();
        $this->assertEquals($courier->id, $order->courier_id);
        $this->assertNotNull($order->courier_assigned_at);
        $this->assertNull($order->pool_entered_at); // Should leave pool
    }

    /** @test */
    public function it_can_mark_picked_up(): void
    {
        $order = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => Order::STATUS_READY,
        ]);

        $order->markPickedUp();

        $order->refresh();
        $this->assertEquals(Order::STATUS_ON_DELIVERY, $order->status);
        $this->assertNotNull($order->picked_up_at);
    }

    /** @test */
    public function it_can_mark_on_way(): void
    {
        $order = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => Order::STATUS_ON_DELIVERY,
        ]);

        $order->markOnWay();

        $this->assertNotNull($order->fresh()->on_way_at);
    }

    /** @test */
    public function it_can_mark_delivered(): void
    {
        $order = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => Order::STATUS_ON_DELIVERY,
        ]);

        $order->markDelivered();

        $order->refresh();
        $this->assertEquals(Order::STATUS_DELIVERED, $order->status);
        $this->assertNotNull($order->delivered_at);
    }

    /** @test */
    public function it_returns_correct_status_colors(): void
    {
        $this->assertEquals('yellow', $this->createOrderWithStatus(Order::STATUS_PENDING)->getStatusColor());
        $this->assertEquals('blue', $this->createOrderWithStatus(Order::STATUS_PREPARING)->getStatusColor());
        $this->assertEquals('purple', $this->createOrderWithStatus(Order::STATUS_READY)->getStatusColor());
        $this->assertEquals('orange', $this->createOrderWithStatus(Order::STATUS_ON_DELIVERY)->getStatusColor());
        $this->assertEquals('green', $this->createOrderWithStatus(Order::STATUS_DELIVERED)->getStatusColor());
        $this->assertEquals('red', $this->createOrderWithStatus(Order::STATUS_CANCELLED)->getStatusColor());
    }

    /** @test */
    public function it_returns_correct_payment_method_labels(): void
    {
        $cashOrder = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'payment_method' => Order::PAYMENT_CASH,
        ]);

        $cardOrder = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'payment_method' => Order::PAYMENT_CARD,
        ]);

        $this->assertEquals('Nakit', $cashOrder->getPaymentMethodLabel());
        $this->assertEquals('Kredi Kartı', $cardOrder->getPaymentMethodLabel());
    }

    /** @test */
    public function it_determines_if_order_can_be_cancelled(): void
    {
        $pendingOrder = $this->createOrderWithStatus(Order::STATUS_PENDING);
        $preparingOrder = $this->createOrderWithStatus(Order::STATUS_PREPARING);
        $readyOrder = $this->createOrderWithStatus(Order::STATUS_READY);
        $deliveredOrder = $this->createOrderWithStatus(Order::STATUS_DELIVERED);

        $this->assertTrue($pendingOrder->canBeCancelled());
        $this->assertTrue($preparingOrder->canBeCancelled());
        $this->assertFalse($readyOrder->canBeCancelled());
        $this->assertFalse($deliveredOrder->canBeCancelled());
    }

    /** @test */
    public function it_calculates_delivery_time(): void
    {
        $order = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'created_at' => now()->subMinutes(30),
            'delivered_at' => now(),
        ]);

        $this->assertEquals(30, $order->getDeliveryTimeInMinutes());
    }

    /** @test */
    public function it_returns_null_for_delivery_time_when_not_delivered(): void
    {
        $order = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'delivered_at' => null,
        ]);

        $this->assertNull($order->getDeliveryTimeInMinutes());
    }

    /** @test */
    public function it_can_save_pod(): void
    {
        $order = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
        ]);

        $order->savePod('photos/pod_123.jpg', ['lat' => 41.0, 'lng' => 29.0], 'Kapıda teslim edildi');

        $order->refresh();
        $this->assertTrue($order->hasPod());
        $this->assertEquals('photos/pod_123.jpg', $order->pod_photo_path);
        $this->assertNotNull($order->pod_timestamp);
        $this->assertEquals(['lat' => 41.0, 'lng' => 29.0], $order->pod_location);
        $this->assertEquals('Kapıda teslim edildi', $order->pod_note);
    }

    /** @test */
    public function it_returns_pod_info_when_available(): void
    {
        $order = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'pod_photo_path' => 'photos/pod.jpg',
            'pod_timestamp' => now(),
            'pod_location' => ['lat' => 41.0, 'lng' => 29.0],
            'pod_note' => 'Test note',
        ]);

        $podInfo = $order->getPodInfo();

        $this->assertNotNull($podInfo);
        $this->assertArrayHasKey('photo_url', $podInfo);
        $this->assertArrayHasKey('timestamp', $podInfo);
        $this->assertArrayHasKey('location', $podInfo);
        $this->assertArrayHasKey('note', $podInfo);
    }

    /** @test */
    public function it_returns_null_pod_info_when_not_available(): void
    {
        $order = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'pod_photo_path' => null,
        ]);

        $this->assertNull($order->getPodInfo());
    }

    /** @test */
    public function it_gets_tracking_steps(): void
    {
        $order = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => Order::STATUS_PREPARING,
        ]);

        $steps = $order->getTrackingSteps();

        $this->assertCount(6, $steps);
        $this->assertEquals('created', $steps[0]['key']);
        $this->assertEquals('delivered', $steps[5]['key']);
    }

    /** @test */
    public function it_gets_current_step(): void
    {
        $this->assertEquals('created', $this->createOrderWithStatus(Order::STATUS_PENDING)->getCurrentStep());
        $this->assertEquals('preparing', $this->createOrderWithStatus(Order::STATUS_PREPARING)->getCurrentStep());
        $this->assertEquals('ready', $this->createOrderWithStatus(Order::STATUS_READY)->getCurrentStep());
        $this->assertEquals('delivered', $this->createOrderWithStatus(Order::STATUS_DELIVERED)->getCurrentStep());
    }

    /** @test */
    public function it_calculates_estimated_minutes_remaining(): void
    {
        $deliveredOrder = $this->createOrderWithStatus(Order::STATUS_DELIVERED);
        $pendingOrder = $this->createOrderWithStatus(Order::STATUS_PENDING);

        $this->assertEquals(0, $deliveredOrder->getEstimatedMinutesRemaining());
        $this->assertEquals(45, $pendingOrder->getEstimatedMinutesRemaining());
    }

    /** @test */
    public function active_scope_returns_correct_orders(): void
    {
        $activeOrder = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => Order::STATUS_PREPARING,
        ]);

        $completedOrder = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => Order::STATUS_DELIVERED,
        ]);

        $activeOrders = Order::active()->get();

        $this->assertTrue($activeOrders->contains($activeOrder));
        $this->assertFalse($activeOrders->contains($completedOrder));
    }

    /** @test */
    public function today_scope_returns_only_todays_orders(): void
    {
        $todayOrder = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'created_at' => now(),
        ]);

        $yesterdayOrder = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'created_at' => now()->subDay(),
        ]);

        $todayOrders = Order::today()->get();

        $this->assertTrue($todayOrders->contains($todayOrder));
        $this->assertFalse($todayOrders->contains($yesterdayOrder));
    }

    /** @test */
    public function in_pool_scope_returns_pool_orders(): void
    {
        $inPoolOrder = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => Order::STATUS_READY,
            'courier_id' => null,
            'pool_entered_at' => now(),
        ]);

        $notInPoolOrder = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => Order::STATUS_PENDING,
            'pool_entered_at' => null,
        ]);

        $poolOrders = Order::inPool()->get();

        $this->assertTrue($poolOrders->contains($inPoolOrder));
        $this->assertFalse($poolOrders->contains($notInPoolOrder));
    }

    /**
     * Helper to create order with specific status
     */
    protected function createOrderWithStatus(string $status): Order
    {
        return Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $this->customer->id,
            'status' => $status,
        ]);
    }
}
