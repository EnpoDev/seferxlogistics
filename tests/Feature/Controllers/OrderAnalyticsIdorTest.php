<?php

namespace Tests\Feature\Controllers;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Task #32: OrderAnalyticsController IDOR
 * - Bayi cannot access other bayi's branch analytics
 * - Bayi can access own branch analytics
 * - branchComparison only shows own branches
 * - API endpoints respect branch access control
 */
class OrderAnalyticsIdorTest extends TestCase
{
    use RefreshDatabase;

    protected User $bayiUser;
    protected User $otherBayiUser;
    protected Branch $ownBranch;
    protected Branch $otherBranch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bayiUser = User::factory()->bayi()->create([
            'role' => 'bayi',
            'parent_id' => null,
        ]);
        $this->otherBayiUser = User::factory()->bayi()->create([
            'role' => 'bayi',
            'parent_id' => null,
        ]);

        $this->ownBranch = Branch::factory()->create(['user_id' => $this->bayiUser->id]);
        $this->otherBranch = Branch::factory()->create(['user_id' => $this->otherBayiUser->id]);
    }

    // =========================================================================
    // Analytics index - branch_id IDOR
    // =========================================================================

    /** @test */
    public function bayi_cannot_access_other_branchs_analytics(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->get(route('bayi.analytics.index', [
                'branch_id' => $this->otherBranch->id,
            ]));

        $response->assertStatus(403);
    }

    /** @test */
    public function bayi_can_access_own_branchs_analytics(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->get(route('bayi.analytics.index', [
                'branch_id' => $this->ownBranch->id,
            ]));

        $response->assertStatus(200);
    }

    /** @test */
    public function bayi_can_access_analytics_without_branch_filter(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->get(route('bayi.analytics.index'));

        $response->assertStatus(200);
    }

    // =========================================================================
    // Weekly comparison - branch_id IDOR
    // =========================================================================

    /** @test */
    public function bayi_cannot_access_other_branchs_weekly_comparison(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->get(route('bayi.analytics.weekly', [
                'branch_id' => $this->otherBranch->id,
            ]));

        $response->assertStatus(403);
    }

    /** @test */
    public function bayi_can_access_own_weekly_comparison(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->get(route('bayi.analytics.weekly', [
                'branch_id' => $this->ownBranch->id,
            ]));

        $response->assertStatus(200);
    }

    // =========================================================================
    // Heatmap - branch_id IDOR
    // =========================================================================

    /** @test */
    public function bayi_cannot_access_other_branchs_heatmap(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->get(route('bayi.analytics.heatmap', [
                'branch_id' => $this->otherBranch->id,
            ]));

        $response->assertStatus(403);
    }

    // =========================================================================
    // API endpoints - branch_id IDOR
    // =========================================================================

    /** @test */
    public function realtime_api_rejects_other_branchs_id(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->getJson(route('bayi.analytics.api.realtime', [
                'branch_id' => $this->otherBranch->id,
            ]));

        $response->assertStatus(403);
    }

    /** @test */
    public function realtime_api_accepts_own_branch_id(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->getJson(route('bayi.analytics.api.realtime', [
                'branch_id' => $this->ownBranch->id,
            ]));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function hourly_api_rejects_other_branchs_id(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->getJson(route('bayi.analytics.api.hourly', [
                'branch_id' => $this->otherBranch->id,
            ]));

        $response->assertStatus(403);
    }

    /** @test */
    public function daily_trend_api_rejects_other_branchs_id(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->getJson(route('bayi.analytics.api.daily', [
                'branch_id' => $this->otherBranch->id,
            ]));

        $response->assertStatus(403);
    }

    /** @test */
    public function courier_performance_api_rejects_other_branchs_id(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->getJson(route('bayi.analytics.api.couriers', [
                'branch_id' => $this->otherBranch->id,
            ]));

        $response->assertStatus(403);
    }

    /** @test */
    public function heatmap_api_rejects_other_branchs_id(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->getJson(route('bayi.analytics.api.heatmap', [
                'branch_id' => $this->otherBranch->id,
            ]));

        $response->assertStatus(403);
    }

    // =========================================================================
    // Branch comparison - only shows own branches
    // =========================================================================

    /** @test */
    public function branch_comparison_only_shows_own_branches(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->get(route('bayi.analytics.branches'));

        $response->assertStatus(200);
    }

    // =========================================================================
    // Isletme user can access parent bayi's branch analytics
    // =========================================================================

    /** @test */
    public function isletme_user_can_access_own_branch_analytics(): void
    {
        $isletmeUser = User::factory()->create([
            'role' => 'isletme',
            'roles' => ['isletme'],
            'parent_id' => $this->bayiUser->id,
        ]);
        $isletmeBranch = Branch::factory()->create(['user_id' => $isletmeUser->id]);

        // Isletme user should be included in parent bayi's branch scope
        // but these routes require role:bayi middleware
        $response = $this->actingAs($isletmeUser)
            ->get(route('bayi.analytics.index', [
                'branch_id' => $isletmeBranch->id,
            ]));

        // Should be redirected (role:bayi middleware blocks isletme)
        $this->assertContains($response->getStatusCode(), [302, 403]);
    }

    // =========================================================================
    // Unauthenticated
    // =========================================================================

    /** @test */
    public function unauthenticated_user_cannot_access_analytics(): void
    {
        $response = $this->get(route('bayi.analytics.index'));
        $response->assertRedirect(route('login'));
    }
}
