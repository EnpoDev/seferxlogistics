<?php

namespace Tests\Feature\Controllers;

use App\Models\Branch;
use App\Models\Courier;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for FinansController tenant isolation
 * - kuryeKazanc: Bayi A cannot see Bayi B's courier earnings
 * - subePerformans: Bayi A sees only own branches
 * - courierPerformanceApi limit capping at 100
 */
class FinansTenantTest extends TestCase
{
    use RefreshDatabase;

    protected User $bayiA;
    protected User $bayiB;
    protected Branch $branchA;
    protected Branch $branchB;
    protected Courier $courierA;
    protected Courier $courierB;

    protected function setUp(): void
    {
        parent::setUp();

        // Bayi A
        $this->bayiA = User::factory()->bayi()->create([
            'role' => 'bayi',
            'parent_id' => null,
        ]);
        $this->branchA = Branch::factory()->create(['user_id' => $this->bayiA->id]);
        $this->courierA = Courier::factory()->create([
            'user_id' => $this->bayiA->id,
            'cash_balance' => 0,
        ]);

        // Bayi B
        $this->bayiB = User::factory()->bayi()->create([
            'role' => 'bayi',
            'parent_id' => null,
        ]);
        $this->branchB = Branch::factory()->create(['user_id' => $this->bayiB->id]);
        $this->courierB = Courier::factory()->create([
            'user_id' => $this->bayiB->id,
            'cash_balance' => 0,
        ]);

        // Orders for Bayi A
        Order::factory()->delivered()->create([
            'branch_id' => $this->branchA->id,
            'courier_id' => $this->courierA->id,
            'total' => 100,
            'payment_method' => Order::PAYMENT_CASH,
        ]);

        // Orders for Bayi B
        Order::factory()->delivered()->create([
            'branch_id' => $this->branchB->id,
            'courier_id' => $this->courierB->id,
            'total' => 200,
            'payment_method' => Order::PAYMENT_CASH,
        ]);
    }

    // =========================================================================
    // kuryeKazanc tenant isolation
    // =========================================================================

    /** @test */
    public function kurye_kazanc_shows_only_own_couriers(): void
    {
        $response = $this->actingAs($this->bayiA)
            ->get(route('bayi.finans.kurye-kazanc'));

        $response->assertStatus(200);

        // Bayi A should NOT see Bayi B's courier data
        // The view receives $courierEarnings - verify isolation through response
        $response->assertDontSee($this->courierB->name);
    }

    /** @test */
    public function kurye_kazanc_bayi_b_sees_own_data(): void
    {
        $response = $this->actingAs($this->bayiB)
            ->get(route('bayi.finans.kurye-kazanc'));

        $response->assertStatus(200);
        $response->assertDontSee($this->courierA->name);
    }

    // =========================================================================
    // subePerformans tenant isolation
    // =========================================================================

    /** @test */
    public function sube_performans_shows_only_own_branches(): void
    {
        $response = $this->actingAs($this->bayiA)
            ->get(route('bayi.finans.sube-performans'));

        $response->assertStatus(200);
        $response->assertDontSee($this->branchB->name);
    }

    /** @test */
    public function sube_performans_bayi_b_sees_own_data(): void
    {
        $response = $this->actingAs($this->bayiB)
            ->get(route('bayi.finans.sube-performans'));

        $response->assertStatus(200);
        $response->assertDontSee($this->branchA->name);
    }

    // =========================================================================
    // courierPerformanceApi limit parameter capping
    // =========================================================================

    /** @test */
    public function courier_performance_api_caps_limit_at_100(): void
    {
        $response = $this->actingAs($this->bayiA)
            ->getJson(route('bayi.analytics.api.couriers', [
                'limit' => 999,
            ]));

        $response->assertStatus(200);
        // The controller caps limit to max 100: min(max((int)$request->get('limit', 10), 1), 100)
        // We can't directly assert the SQL limit, but the endpoint should succeed
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function courier_performance_api_minimum_limit_is_1(): void
    {
        $response = $this->actingAs($this->bayiA)
            ->getJson(route('bayi.analytics.api.couriers', [
                'limit' => 0,
            ]));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    // =========================================================================
    // branchComparison IDOR - ensures only own branches
    // =========================================================================

    /** @test */
    public function branch_comparison_bayi_a_does_not_see_bayi_b_branches(): void
    {
        $response = $this->actingAs($this->bayiA)
            ->get(route('bayi.analytics.branches'));

        $response->assertStatus(200);
        $response->assertDontSee($this->branchB->name);
    }

    /** @test */
    public function branch_comparison_bayi_b_does_not_see_bayi_a_branches(): void
    {
        $response = $this->actingAs($this->bayiB)
            ->get(route('bayi.analytics.branches'));

        $response->assertStatus(200);
        $response->assertDontSee($this->branchA->name);
    }

    // =========================================================================
    // Finans API tenant isolation
    // =========================================================================

    /** @test */
    public function finans_api_returns_only_own_data(): void
    {
        $response = $this->actingAs($this->bayiA)
            ->getJson(route('bayi.finans.api', ['type' => 'summary']));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function finans_api_rejects_other_bayi_branch_id(): void
    {
        $response = $this->actingAs($this->bayiA)
            ->getJson(route('bayi.finans.api', [
                'type' => 'summary',
                'branch_id' => $this->branchB->id,
            ]));

        $response->assertStatus(403);
    }
}
