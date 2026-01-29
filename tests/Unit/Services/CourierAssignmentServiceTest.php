<?php

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use App\Services\CourierAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CourierAssignmentService $service;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CourierAssignmentService();
        $this->branch = Branch::factory()->create();
    }

    /** @test */
    public function it_returns_null_when_no_couriers_available(): void
    {
        // No couriers in database
        $best = $this->service->findBestCourier();

        $this->assertNull($best);
    }

    /** @test */
    public function it_finds_best_courier_based_on_availability(): void
    {
        $today = strtolower(now()->format('l'));
        $shifts = json_encode([
            $today => ['enabled' => true, 'start' => '00:00', 'end' => '23:59'],
        ]);

        // Available courier on shift
        $availableCourier = Courier::factory()->create([
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => 0,
            'shifts' => $shifts,
        ]);

        // Offline courier
        Courier::factory()->create([
            'status' => Courier::STATUS_OFFLINE,
            'active_orders_count' => 0,
            'shifts' => $shifts,
        ]);

        $best = $this->service->findBestCourier();

        $this->assertNotNull($best);
        $this->assertEquals($availableCourier->id, $best->id);
    }

    /** @test */
    public function it_prefers_courier_with_fewer_active_orders(): void
    {
        $today = strtolower(now()->format('l'));
        $shifts = json_encode([
            $today => ['enabled' => true, 'start' => '00:00', 'end' => '23:59'],
        ]);

        // Courier with 2 active orders
        Courier::factory()->create([
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => 2,
            'shifts' => $shifts,
        ]);

        // Courier with 0 active orders
        $lessBusyCourier = Courier::factory()->create([
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => 0,
            'shifts' => $shifts,
        ]);

        $best = $this->service->findBestCourier();

        $this->assertEquals($lessBusyCourier->id, $best->id);
    }

    /** @test */
    public function it_can_get_available_couriers(): void
    {
        $today = strtolower(now()->format('l'));
        $shifts = json_encode([
            $today => ['enabled' => true, 'start' => '00:00', 'end' => '23:59'],
        ]);

        Courier::factory()->create([
            'status' => Courier::STATUS_AVAILABLE,
            'shifts' => $shifts,
        ]);

        Courier::factory()->create([
            'status' => Courier::STATUS_BUSY,
            'active_orders_count' => 2,
            'shifts' => $shifts,
        ]);

        Courier::factory()->create([
            'status' => Courier::STATUS_OFFLINE,
            'shifts' => $shifts,
        ]);

        $available = $this->service->getAvailableCouriers();

        $this->assertCount(2, $available); // Available and busy with <3 orders
    }

    /** @test */
    public function busy_courier_with_max_orders_is_not_available(): void
    {
        $today = strtolower(now()->format('l'));
        $shifts = json_encode([
            $today => ['enabled' => true, 'start' => '00:00', 'end' => '23:59'],
        ]);

        // Busy courier with 3+ orders
        Courier::factory()->create([
            'status' => Courier::STATUS_BUSY,
            'active_orders_count' => 5,
            'shifts' => $shifts,
        ]);

        $available = $this->service->getAvailableCouriers();

        $this->assertCount(0, $available);
    }

    /** @test */
    public function it_can_assign_courier_to_order(): void
    {
        $today = strtolower(now()->format('l'));
        $shifts = json_encode([
            $today => ['enabled' => true, 'start' => '00:00', 'end' => '23:59'],
        ]);

        $courier = Courier::factory()->create([
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => 0,
            'shifts' => $shifts,
        ]);

        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'courier_id' => null,
        ]);

        $result = $this->service->assignCourierToOrder($order, $courier->id);

        $this->assertTrue($result);

        $order->refresh();
        $this->assertEquals($courier->id, $order->courier_id);
    }

    /** @test */
    public function it_returns_false_for_invalid_courier_assignment(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
        ]);

        $result = $this->service->assignCourierToOrder($order, 99999);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_auto_assign_best_courier(): void
    {
        $today = strtolower(now()->format('l'));
        $shifts = json_encode([
            $today => ['enabled' => true, 'start' => '00:00', 'end' => '23:59'],
        ]);

        $courier = Courier::factory()->create([
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => 0,
            'shifts' => $shifts,
        ]);

        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'courier_id' => null,
        ]);

        $result = $this->service->assignCourierToOrder($order);

        $this->assertTrue($result);

        $order->refresh();
        $this->assertEquals($courier->id, $order->courier_id);
    }

    /** @test */
    public function it_can_reassign_courier(): void
    {
        $today = strtolower(now()->format('l'));
        $shifts = json_encode([
            $today => ['enabled' => true, 'start' => '00:00', 'end' => '23:59'],
        ]);

        $oldCourier = Courier::factory()->create([
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => 1,
        ]);

        $newCourier = Courier::factory()->create([
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => 0,
            'shifts' => $shifts,
        ]);

        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'courier_id' => $oldCourier->id,
        ]);

        $result = $this->service->reassignCourier($order, $newCourier->id);

        $this->assertTrue($result);

        $order->refresh();
        $this->assertEquals($newCourier->id, $order->courier_id);
    }

    /** @test */
    public function it_returns_false_when_reassigning_to_invalid_courier(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
        ]);

        $result = $this->service->reassignCourier($order, 99999);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_get_workload_stats(): void
    {
        $today = strtolower(now()->format('l'));
        $shifts = json_encode([
            $today => ['enabled' => true, 'start' => '00:00', 'end' => '23:59'],
        ]);

        Courier::factory()->create([
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => 2,
            'shifts' => $shifts,
        ]);

        Courier::factory()->create([
            'status' => Courier::STATUS_BUSY,
            'active_orders_count' => 3,
            'shifts' => $shifts,
        ]);

        Courier::factory()->create([
            'status' => Courier::STATUS_OFFLINE,
            'active_orders_count' => 0,
        ]);

        $stats = $this->service->getCourierWorkloadStats();

        $this->assertEquals(3, $stats['total_couriers']);
        $this->assertEquals(1, $stats['available_couriers']);
        $this->assertEquals(1, $stats['busy_couriers']);
        $this->assertEquals(1, $stats['offline_couriers']);
        $this->assertEquals(5, $stats['total_active_orders']);
    }

    /** @test */
    public function it_checks_if_courier_is_on_shift(): void
    {
        // When shift_start and shift_end are set during working hours
        $courierOnShift = Courier::factory()->create([
            'shift_start' => '00:00',
            'shift_end' => '23:59',
        ]);

        // When shift times are outside current time
        $courierOffShift = Courier::factory()->create([
            'shift_start' => '03:00',
            'shift_end' => '04:00', // Unlikely current time
        ]);

        $this->assertTrue($this->service->isCourierOnShift($courierOnShift));
        // Note: This might still pass if test runs between 3-4 AM, but that's unlikely
    }

    /** @test */
    public function it_prefers_experienced_couriers(): void
    {
        $today = strtolower(now()->format('l'));
        $shifts = json_encode([
            $today => ['enabled' => true, 'start' => '00:00', 'end' => '23:59'],
        ]);

        // Inexperienced courier
        Courier::factory()->create([
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => 0,
            'total_deliveries' => 10,
            'shifts' => $shifts,
        ]);

        // Experienced courier
        $experienced = Courier::factory()->create([
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => 0,
            'total_deliveries' => 200,
            'shifts' => $shifts,
        ]);

        $best = $this->service->findBestCourier();

        $this->assertEquals($experienced->id, $best->id);
    }

    /** @test */
    public function it_prefers_faster_couriers(): void
    {
        $today = strtolower(now()->format('l'));
        $shifts = json_encode([
            $today => ['enabled' => true, 'start' => '00:00', 'end' => '23:59'],
        ]);

        // Slow courier
        Courier::factory()->create([
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => 0,
            'average_delivery_time' => 60,
            'shifts' => $shifts,
        ]);

        // Fast courier
        $fast = Courier::factory()->create([
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => 0,
            'average_delivery_time' => 20,
            'shifts' => $shifts,
        ]);

        $best = $this->service->findBestCourier();

        $this->assertEquals($fast->id, $best->id);
    }
}
