<?php

namespace Tests\Feature\Controllers;

use App\Models\Branch;
use App\Models\Courier;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user with bayi role for courier management access
        $this->user = User::factory()->create([
            'roles' => ['bayi'],
        ]);
        $this->branch = Branch::factory()->create();
    }

    /** @test */
    public function authenticated_user_can_view_couriers_list(): void
    {
        Courier::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->get(route('bayi.kuryelerim'));

        $response->assertStatus(200);
    }

    /** @test */
    public function unauthenticated_user_cannot_view_couriers(): void
    {
        $response = $this->get(route('bayi.kuryelerim'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function user_can_view_courier_details(): void
    {
        $courier = Courier::factory()->create();

        // Create some orders for the courier
        Order::factory()->delivered()->count(3)->create([
            'branch_id' => $this->branch->id,
            'courier_id' => $courier->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('bayi.kurye-detay', $courier));

        $response->assertStatus(200);
    }

    /** @test */
    public function courier_can_be_deleted(): void
    {
        $courier = Courier::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('bayi.kurye-sil', $courier));

        $response->assertRedirect();
    }

    /** @test */
    public function courier_statistics_can_be_viewed(): void
    {
        $courier = Courier::factory()->create();

        Order::factory()->delivered()->count(5)->create([
            'branch_id' => $this->branch->id,
            'courier_id' => $courier->id,
            'delivery_fee' => 15.00,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('bayi.kurye-statistics', $courier));

        $response->assertStatus(200);
    }

    /** @test */
    public function courier_past_orders_can_be_viewed(): void
    {
        $courier = Courier::factory()->create();

        Order::factory()->delivered()->count(10)->create([
            'branch_id' => $this->branch->id,
            'courier_id' => $courier->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('bayi.kurye-past-orders', $courier));

        $response->assertStatus(200);
    }
}
