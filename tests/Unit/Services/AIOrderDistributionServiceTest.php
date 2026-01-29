<?php

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use App\Services\AIOrderDistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class AIOrderDistributionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AIOrderDistributionService $aiService;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aiService = new AIOrderDistributionService();

        $this->branch = Branch::factory()->create([
            'name' => 'Test Branch',
            'lat' => 41.0082,
            'lng' => 28.9784,
        ]);
    }

    /** @test */
    public function it_finds_best_courier_for_order(): void
    {
        // Create multiple couriers with different attributes
        $closeCourier = Courier::factory()->create([
            'name' => 'Close Courier',
            'lat' => 41.0090, // Very close to branch
            'lng' => 28.9790,
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => 0,
        ]);

        $farCourier = Courier::factory()->create([
            'name' => 'Far Courier',
            'lat' => 41.0500, // Further from branch
            'lng' => 29.0500,
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => 0,
        ]);

        $order = $this->createOrder();

        $bestCourier = $this->aiService->findBestCourier($order);

        $this->assertNotNull($bestCourier);
        // Close courier should be selected due to distance factor
        $this->assertEquals($closeCourier->id, $bestCourier->id);
    }

    /** @test */
    public function it_returns_null_when_no_couriers_available(): void
    {
        // Create courier but mark as offline
        Courier::factory()->create([
            'status' => Courier::STATUS_OFFLINE,
            'lat' => 41.0090,
            'lng' => 28.9790,
        ]);

        $order = $this->createOrder();

        $bestCourier = $this->aiService->findBestCourier($order);

        $this->assertNull($bestCourier);
    }

    /** @test */
    public function it_considers_workload_in_selection(): void
    {
        // Create courier with high workload
        $busyCourier = Courier::factory()->create([
            'name' => 'Busy Courier',
            'lat' => 41.0085, // Slightly closer
            'lng' => 28.9785,
            'status' => Courier::STATUS_BUSY,
            'active_orders_count' => 4, // Near max
        ]);

        // Create courier with low workload
        $freeCourier = Courier::factory()->create([
            'name' => 'Free Courier',
            'lat' => 41.0090, // Slightly further
            'lng' => 28.9790,
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => 0,
        ]);

        $order = $this->createOrder();

        $bestCourier = $this->aiService->findBestCourier($order);

        // Free courier should be selected despite being slightly further
        $this->assertEquals($freeCourier->id, $bestCourier->id);
    }

    /** @test */
    public function it_excludes_couriers_at_max_capacity(): void
    {
        // Create courier at max capacity
        Courier::factory()->create([
            'lat' => 41.0085,
            'lng' => 28.9785,
            'status' => Courier::STATUS_BUSY,
            'active_orders_count' => 5, // Max capacity
        ]);

        // Create available courier
        $availableCourier = Courier::factory()->create([
            'lat' => 41.0200,
            'lng' => 28.9900,
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => 0,
        ]);

        $order = $this->createOrder();

        $bestCourier = $this->aiService->findBestCourier($order);

        $this->assertNotNull($bestCourier);
        $this->assertEquals($availableCourier->id, $bestCourier->id);
    }

    /** @test */
    public function it_returns_suggested_couriers_list(): void
    {
        // Create multiple couriers
        for ($i = 0; $i < 7; $i++) {
            Courier::factory()->create([
                'lat' => 41.0082 + ($i * 0.01),
                'lng' => 28.9784 + ($i * 0.01),
                'status' => Courier::STATUS_AVAILABLE,
                'active_orders_count' => 0,
            ]);
        }

        $order = $this->createOrder();

        $suggestions = $this->aiService->getSuggestedCouriers($order, 5);

        $this->assertCount(5, $suggestions);

        // Check structure of each suggestion
        $firstSuggestion = $suggestions->first();
        $this->assertArrayHasKey('courier', $firstSuggestion);
        $this->assertArrayHasKey('score', $firstSuggestion);
        $this->assertArrayHasKey('factors', $firstSuggestion);
        $this->assertArrayHasKey('distance_km', $firstSuggestion);
    }

    /** @test */
    public function it_sorts_suggestions_by_score(): void
    {
        // Create couriers at different distances
        Courier::factory()->create([
            'name' => 'Close Courier',
            'lat' => 41.0085,
            'lng' => 28.9785,
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => 0,
        ]);

        Courier::factory()->create([
            'name' => 'Far Courier',
            'lat' => 41.0500,
            'lng' => 29.0500,
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => 0,
        ]);

        $order = $this->createOrder();

        $suggestions = $this->aiService->getSuggestedCouriers($order, 5);

        // First suggestion should have highest score
        $scores = $suggestions->pluck('score')->toArray();
        $sortedScores = Arr::sort($scores);
        $this->assertEquals($scores, array_reverse($sortedScores));
    }

    /** @test */
    public function it_distributes_pending_orders(): void
    {
        // Create available courier
        $courier = Courier::factory()->create([
            'lat' => 41.0090,
            'lng' => 28.9790,
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => 0,
        ]);

        // Create ready orders without courier
        $this->createOrder(['status' => 'ready', 'courier_id' => null]);
        $this->createOrder(['status' => 'ready', 'courier_id' => null]);

        $results = $this->aiService->distributePendingOrders();

        $this->assertEquals(2, $results['total']);
        $this->assertGreaterThanOrEqual(1, $results['assigned']);
        $this->assertArrayHasKey('assignments', $results);
    }

    /** @test */
    public function it_returns_distribution_stats(): void
    {
        // Create some couriers
        Courier::factory()->create(['status' => Courier::STATUS_AVAILABLE]);
        Courier::factory()->create(['status' => Courier::STATUS_BUSY]);
        Courier::factory()->create(['status' => Courier::STATUS_OFFLINE]);

        $stats = $this->aiService->getDistributionStats();

        $this->assertArrayHasKey('today_assigned', $stats);
        $this->assertArrayHasKey('avg_assignment_time_minutes', $stats);
        $this->assertArrayHasKey('available_couriers', $stats);
        $this->assertArrayHasKey('busy_couriers', $stats);

        $this->assertEquals(1, $stats['available_couriers']);
        $this->assertEquals(1, $stats['busy_couriers']);
    }

    /** @test */
    public function it_considers_courier_performance(): void
    {
        // Create fast courier (further away)
        $fastCourier = Courier::factory()->create([
            'name' => 'Fast Courier',
            'lat' => 41.0150,
            'lng' => 28.9850,
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => 0,
            'average_delivery_time' => 15, // Very fast
            'total_deliveries' => 100,
        ]);

        // Create slow courier (closer)
        $slowCourier = Courier::factory()->create([
            'name' => 'Slow Courier',
            'lat' => 41.0090,
            'lng' => 28.9790,
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => 0,
            'average_delivery_time' => 40, // Slow
            'total_deliveries' => 100,
        ]);

        $order = $this->createOrder();

        $suggestions = $this->aiService->getSuggestedCouriers($order, 2);

        // Both should be included with their performance scores
        $this->assertCount(2, $suggestions);

        // Check that factors include performance data
        $factors = $suggestions->first()['factors'];
        $this->assertArrayHasKey('performance', $factors);
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
            'status' => 'ready',
            'courier_id' => null,
        ], $attributes));
    }
}
