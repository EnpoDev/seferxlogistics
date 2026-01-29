<?php

namespace Tests\Feature\Controllers;

use App\Models\Branch;
use App\Models\BranchSetting;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'roles' => ['bayi'],
        ]);
        $this->branch = Branch::factory()->create();

        BranchSetting::create([
            'branch_id' => $this->branch->id,
            'pool_enabled' => true,
        ]);
    }

    /** @test */
    public function api_can_search_orders(): void
    {
        Order::factory()->create([
            'branch_id' => $this->branch->id,
            'order_number' => 'ORD-123456',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/orders/search?q=123456');

        $response->assertStatus(200);
    }

    /** @test */
    public function api_can_search_couriers(): void
    {
        Courier::factory()->create([
            'name' => 'Test Courier',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/couriers/search?q=Test');

        $response->assertStatus(200);
    }

    /** @test */
    public function api_can_get_single_courier(): void
    {
        $courier = Courier::factory()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/couriers/{$courier->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function api_can_get_map_data(): void
    {
        Courier::factory()->count(3)->create([
            'status' => Courier::STATUS_AVAILABLE,
            'lat' => 41.0082,
            'lng' => 28.9784,
        ]);

        Order::factory()->count(2)->create([
            'branch_id' => $this->branch->id,
            'status' => 'on_delivery',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/map-data');

        $response->assertStatus(200);
    }

    /** @test */
    public function api_returns_401_for_unauthenticated_map_data(): void
    {
        $response = $this->getJson('/api/map-data');

        $response->assertStatus(401);
    }

    /** @test */
    public function bayi_analytics_api_returns_realtime_data(): void
    {
        Order::factory()->pending()->count(2)->create(['branch_id' => $this->branch->id]);
        Order::factory()->delivered()->count(5)->create(['branch_id' => $this->branch->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/bayi/analytics/api/realtime');

        $response->assertStatus(200);
    }

    /** @test */
    public function bayi_analytics_api_returns_daily_data(): void
    {
        Order::factory()->delivered()->count(5)->create([
            'branch_id' => $this->branch->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/bayi/analytics/api/daily');

        $response->assertStatus(200);
    }

    /** @test */
    public function bayi_analytics_api_returns_hourly_data(): void
    {
        Order::factory()->count(3)->create([
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/bayi/analytics/api/hourly');

        $response->assertStatus(200);
    }

    /** @test */
    public function bayi_analytics_api_returns_courier_data(): void
    {
        $courier = Courier::factory()->create();

        Order::factory()->delivered()->count(3)->create([
            'branch_id' => $this->branch->id,
            'courier_id' => $courier->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/bayi/analytics/api/couriers');

        $response->assertStatus(200);
    }

    /** @test */
    public function bayi_finans_api_returns_data(): void
    {
        Order::factory()->delivered()->count(5)->create([
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/bayi/finans/api');

        $response->assertStatus(200);
    }
}
