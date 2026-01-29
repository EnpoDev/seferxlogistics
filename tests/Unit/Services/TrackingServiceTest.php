<?php

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use App\Services\TrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TrackingService $trackingService;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trackingService = new TrackingService();

        $this->branch = Branch::factory()->create([
            'name' => 'Test Branch',
            'lat' => 41.0082,
            'lng' => 28.9784, // Istanbul coordinates
        ]);
    }

    /** @test */
    public function it_calculates_distance_between_two_points(): void
    {
        // Istanbul to Ankara distance (approximately 350km)
        $distance = $this->trackingService->calculateDistance(
            41.0082, 28.9784, // Istanbul
            39.9334, 32.8597  // Ankara
        );

        // Should be approximately 350km (with some tolerance)
        $this->assertGreaterThan(300, $distance);
        $this->assertLessThan(400, $distance);
    }

    /** @test */
    public function it_calculates_distance_for_same_point_as_zero(): void
    {
        $distance = $this->trackingService->calculateDistance(
            41.0082, 28.9784,
            41.0082, 28.9784
        );

        $this->assertEquals(0.0, $distance);
    }

    /** @test */
    public function it_returns_null_eta_for_delivered_orders(): void
    {
        $order = $this->createOrder(['status' => 'delivered']);

        $eta = $this->trackingService->calculateETA($order);

        $this->assertNull($eta);
    }

    /** @test */
    public function it_returns_null_eta_for_cancelled_orders(): void
    {
        $order = $this->createOrder(['status' => 'cancelled']);

        $eta = $this->trackingService->calculateETA($order);

        $this->assertNull($eta);
    }

    /** @test */
    public function it_returns_static_eta_when_no_courier_assigned(): void
    {
        $order = $this->createOrder([
            'status' => 'pending',
            'courier_id' => null,
        ]);

        $eta = $this->trackingService->calculateETA($order);

        $this->assertNotNull($eta);
        // Pending status should add 45 minutes
        $this->assertTrue($eta->isFuture());
    }

    /** @test */
    public function it_calculates_delivery_progress_for_pending_order(): void
    {
        $order = $this->createOrder(['status' => 'pending']);

        $progress = $this->trackingService->getDeliveryProgress($order);

        $this->assertEquals(10, $progress);
    }

    /** @test */
    public function it_calculates_delivery_progress_for_preparing_order(): void
    {
        $order = $this->createOrder(['status' => 'preparing']);

        $progress = $this->trackingService->getDeliveryProgress($order);

        $this->assertEquals(25, $progress);
    }

    /** @test */
    public function it_calculates_delivery_progress_for_ready_order(): void
    {
        $order = $this->createOrder(['status' => 'ready']);

        $progress = $this->trackingService->getDeliveryProgress($order);

        $this->assertEquals(40, $progress);
    }

    /** @test */
    public function it_returns_100_progress_for_delivered_order(): void
    {
        $order = $this->createOrder(['status' => 'delivered']);

        $progress = $this->trackingService->getDeliveryProgress($order);

        $this->assertEquals(100, $progress);
    }

    /** @test */
    public function it_returns_0_progress_for_cancelled_order(): void
    {
        $order = $this->createOrder(['status' => 'cancelled']);

        $progress = $this->trackingService->getDeliveryProgress($order);

        $this->assertEquals(0, $progress);
    }

    /** @test */
    public function it_updates_courier_location(): void
    {
        $courier = Courier::factory()->create([
            'lat' => 41.0082,
            'lng' => 28.9784,
        ]);

        $newLat = 41.0100;
        $newLng = 28.9800;

        $this->trackingService->updateCourierLocation($courier, $newLat, $newLng);

        $courier->refresh();

        $this->assertEquals($newLat, $courier->lat);
        $this->assertEquals($newLng, $courier->lng);
    }

    /** @test */
    public function it_returns_tracking_data_structure(): void
    {
        $courier = Courier::factory()->create([
            'name' => 'Test Courier',
            'lat' => 41.0090,
            'lng' => 28.9790,
        ]);

        $order = $this->createOrder([
            'status' => 'on_delivery',
            'courier_id' => $courier->id,
        ]);

        $trackingData = $this->trackingService->getTrackingData($order);

        $this->assertArrayHasKey('order', $trackingData);
        $this->assertArrayHasKey('tracking', $trackingData);
        $this->assertArrayHasKey('courier', $trackingData);
        $this->assertArrayHasKey('branch', $trackingData);

        // Check order structure
        $this->assertArrayHasKey('id', $trackingData['order']);
        $this->assertArrayHasKey('status', $trackingData['order']);
        $this->assertArrayHasKey('customer_name', $trackingData['order']);

        // Check tracking structure
        $this->assertArrayHasKey('progress', $trackingData['tracking']);
        $this->assertArrayHasKey('estimated_minutes', $trackingData['tracking']);
    }

    /** @test */
    public function it_calculates_eta_with_courier_location(): void
    {
        $courier = Courier::factory()->create([
            'lat' => 41.0090,
            'lng' => 28.9790,
            'status' => Courier::STATUS_BUSY,
        ]);

        $order = $this->createOrder([
            'status' => 'on_delivery',
            'courier_id' => $courier->id,
            'lat' => 41.0200, // Customer location
            'lng' => 28.9900,
        ]);

        $eta = $this->trackingService->calculateETA($order);

        $this->assertNotNull($eta);
        $this->assertTrue($eta->isFuture());

        // Verify order was updated
        $order->refresh();
        $this->assertNotNull($order->estimated_minutes);
        $this->assertNotNull($order->estimated_delivery_at);
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
            'lat' => 41.0100,
            'lng' => 28.9800,
            'status' => 'pending',
        ], $attributes));
    }
}
