<?php

namespace Tests\Feature\Controllers;

use App\Models\Branch;
use App\Models\PricingPolicy;
use App\Models\PricingPolicyRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Task #14: Isletme Fiyatlandirmalari
 * - storePricingPolicy creates PricingPolicyRules correctly
 * - fixed/unit_price types create single rule
 * - package_based/distance_based types create multiple rules
 * - getPricingForBranch filters by type correctly
 * - Policy without rules returns default values
 */
class PricingPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected User $bayiUser;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bayiUser = User::factory()->bayi()->create([
            'role' => 'bayi',
            'parent_id' => null,
        ]);

        $this->branch = Branch::factory()->create(['user_id' => $this->bayiUser->id]);
    }

    // =========================================================================
    // storePricingPolicy - fixed type creates single rule
    // =========================================================================

    /** @test */
    public function store_fixed_policy_creates_policy_and_single_rule(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->postJson(route('bayi.isletme.pricing-policies.store', $this->branch), [
                'type' => 'business',
                'policy_type' => 'fixed',
                'name' => 'Sabit Fiyat Politikasi',
                'description' => 'Test aciklama',
                'is_active' => true,
                'rule_price' => 15.00,
                'rule_percentage' => 0,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('pricing_policies', [
            'branch_id' => $this->branch->id,
            'type' => 'business',
            'policy_type' => 'fixed',
            'name' => 'Sabit Fiyat Politikasi',
            'is_active' => true,
        ]);

        $policy = PricingPolicy::where('branch_id', $this->branch->id)->first();
        $this->assertNotNull($policy);
        $this->assertEquals(1, $policy->rules()->count(), 'Fixed policy should have exactly 1 rule');

        $rule = $policy->rules()->first();
        $this->assertEquals(0, (float) $rule->min_value);
        $this->assertEquals(999999, (float) $rule->max_value);
        $this->assertEquals(15.00, (float) $rule->price);
    }

    /** @test */
    public function store_unit_price_policy_creates_single_rule(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->postJson(route('bayi.isletme.pricing-policies.store', $this->branch), [
                'type' => 'courier',
                'policy_type' => 'unit_price',
                'name' => 'Birim Fiyat Politikasi',
                'is_active' => true,
                'rule_price' => 2.50,
                'rule_percentage' => 0,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $policy = PricingPolicy::where('branch_id', $this->branch->id)
            ->where('policy_type', 'unit_price')
            ->first();

        $this->assertNotNull($policy);
        $this->assertEquals(1, $policy->rules()->count(), 'unit_price policy should have exactly 1 rule');
        $this->assertEquals(2.50, (float) $policy->rules()->first()->price);
    }

    /** @test */
    public function store_fixed_policy_with_percentage_creates_rule(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->postJson(route('bayi.isletme.pricing-policies.store', $this->branch), [
                'type' => 'business',
                'policy_type' => 'fixed',
                'name' => 'Yuzde Politikasi',
                'is_active' => true,
                'rule_price' => 0,
                'rule_percentage' => 60,
            ]);

        $response->assertStatus(200);

        $policy = PricingPolicy::where('name', 'Yuzde Politikasi')->first();
        $this->assertNotNull($policy);
        $this->assertEquals(1, $policy->rules()->count());
        $this->assertEquals(60, (float) $policy->rules()->first()->percentage);
    }

    // =========================================================================
    // storePricingPolicy - package_based creates multiple rules
    // =========================================================================

    /** @test */
    public function store_package_based_policy_creates_multiple_rules(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->postJson(route('bayi.isletme.pricing-policies.store', $this->branch), [
                'type' => 'business',
                'policy_type' => 'package_based',
                'name' => 'Paket Bazli Politika',
                'is_active' => true,
                'rules' => [
                    ['min_value' => 0, 'max_value' => 100, 'price' => 10, 'percentage' => 0],
                    ['min_value' => 100, 'max_value' => 500, 'price' => 8, 'percentage' => 0],
                    ['min_value' => 500, 'max_value' => 9999, 'price' => 5, 'percentage' => 0],
                ],
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $policy = PricingPolicy::where('name', 'Paket Bazli Politika')->first();
        $this->assertNotNull($policy);
        $this->assertEquals(3, $policy->rules()->count(), 'package_based policy should have 3 rules');

        // Verify rule ordering
        $rules = $policy->rules()->orderBy('order')->get();
        $this->assertEquals(1, $rules[0]->order);
        $this->assertEquals(2, $rules[1]->order);
        $this->assertEquals(3, $rules[2]->order);

        // Verify rule values
        $this->assertEquals(10, (float) $rules[0]->price);
        $this->assertEquals(8, (float) $rules[1]->price);
        $this->assertEquals(5, (float) $rules[2]->price);
    }

    /** @test */
    public function store_distance_based_policy_creates_multiple_rules(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->postJson(route('bayi.isletme.pricing-policies.store', $this->branch), [
                'type' => 'courier',
                'policy_type' => 'distance_based',
                'name' => 'Mesafe Bazli Politika',
                'is_active' => true,
                'rules' => [
                    ['min_value' => 0, 'max_value' => 3, 'price' => 15, 'percentage' => 0],
                    ['min_value' => 3, 'max_value' => 10, 'price' => 20, 'percentage' => 0],
                ],
            ]);

        $response->assertStatus(200);

        $policy = PricingPolicy::where('name', 'Mesafe Bazli Politika')->first();
        $this->assertNotNull($policy);
        $this->assertEquals(2, $policy->rules()->count(), 'distance_based policy should have 2 rules');
    }

    /** @test */
    public function store_package_based_skips_rules_with_zero_values(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->postJson(route('bayi.isletme.pricing-policies.store', $this->branch), [
                'type' => 'business',
                'policy_type' => 'package_based',
                'name' => 'Filtreli Politika',
                'is_active' => true,
                'rules' => [
                    ['min_value' => 0, 'max_value' => 100, 'price' => 10, 'percentage' => 0],
                    ['min_value' => 100, 'max_value' => 500, 'price' => 0, 'percentage' => 0], // zero - should be skipped
                    ['min_value' => 500, 'max_value' => 9999, 'price' => 5, 'percentage' => 0],
                ],
            ]);

        $response->assertStatus(200);

        $policy = PricingPolicy::where('name', 'Filtreli Politika')->first();
        $this->assertEquals(2, $policy->rules()->count(), 'Rules with zero price and zero percentage should be skipped');
    }

    // =========================================================================
    // storePricingPolicy - fixed type without price/percentage creates no rules
    // =========================================================================

    /** @test */
    public function store_fixed_policy_without_price_creates_no_rules(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->postJson(route('bayi.isletme.pricing-policies.store', $this->branch), [
                'type' => 'business',
                'policy_type' => 'fixed',
                'name' => 'Kuralsiz Politika',
                'is_active' => false,
            ]);

        $response->assertStatus(200);

        $policy = PricingPolicy::where('name', 'Kuralsiz Politika')->first();
        $this->assertNotNull($policy);
        $this->assertEquals(0, $policy->rules()->count(), 'Policy without price should have no rules');
    }

    // =========================================================================
    // getPricingForBranch - type filtering
    // =========================================================================

    /** @test */
    public function get_pricing_for_branch_filters_by_type(): void
    {
        // Create business pricing policy
        $businessPolicy = PricingPolicy::create([
            'branch_id' => $this->branch->id,
            'type' => 'business',
            'policy_type' => 'fixed',
            'name' => 'Isletme Politikasi',
            'is_active' => true,
        ]);
        $businessPolicy->rules()->create([
            'min_value' => 0,
            'max_value' => 999999,
            'price' => 20,
            'percentage' => 0,
            'order' => 1,
        ]);

        // Create courier pricing policy
        $courierPolicy = PricingPolicy::create([
            'branch_id' => $this->branch->id,
            'type' => 'courier',
            'policy_type' => 'fixed',
            'name' => 'Kurye Politikasi',
            'is_active' => true,
        ]);
        $courierPolicy->rules()->create([
            'min_value' => 0,
            'max_value' => 999999,
            'price' => 8,
            'percentage' => 0,
            'order' => 1,
        ]);

        // Query business type
        $businessPricing = PricingPolicy::getPricingForBranch($this->branch->id, 'business');
        $this->assertEquals('Isletme Politikasi', $businessPricing['policy_name']);
        $this->assertEquals(20, $businessPricing['km_rate_courier']); // price maps to km_rate_courier

        // Query courier type
        $courierPricing = PricingPolicy::getPricingForBranch($this->branch->id, 'courier');
        $this->assertEquals('Kurye Politikasi', $courierPricing['policy_name']);
        $this->assertEquals(8, $courierPricing['km_rate_courier']);
    }

    /** @test */
    public function get_pricing_for_branch_ignores_inactive_policies(): void
    {
        PricingPolicy::create([
            'branch_id' => $this->branch->id,
            'type' => 'business',
            'policy_type' => 'fixed',
            'name' => 'Pasif Politika',
            'is_active' => false,
        ]);

        $pricing = PricingPolicy::getPricingForBranch($this->branch->id, 'business');
        $this->assertEquals('Standart Politika', $pricing['policy_name'], 'Inactive policy should be ignored, returning default');
    }

    /** @test */
    public function get_pricing_for_branch_with_percentage_rule(): void
    {
        $policy = PricingPolicy::create([
            'branch_id' => $this->branch->id,
            'type' => 'business',
            'policy_type' => 'fixed',
            'name' => 'Yuzde Politikasi',
            'is_active' => true,
        ]);
        $policy->rules()->create([
            'min_value' => 0,
            'max_value' => 999999,
            'price' => 0,
            'percentage' => 70,
            'order' => 1,
        ]);

        $pricing = PricingPolicy::getPricingForBranch($this->branch->id, 'business');
        $this->assertEquals('Yuzde Politikasi', $pricing['policy_name']);
        $this->assertEqualsWithDelta(0.70, $pricing['courier_percentage'], 0.001);
        $this->assertEqualsWithDelta(0.30, $pricing['branch_percentage'], 0.001);
    }

    // =========================================================================
    // getPricingForBranch - null/missing returns defaults
    // =========================================================================

    /** @test */
    public function get_pricing_for_null_branch_returns_defaults(): void
    {
        $pricing = PricingPolicy::getPricingForBranch(null);
        $defaults = PricingPolicy::getDefaultPricing();

        $this->assertEquals($defaults, $pricing);
    }

    /** @test */
    public function get_pricing_for_branch_without_policy_returns_defaults(): void
    {
        // Branch exists but has no pricing policies
        $pricing = PricingPolicy::getPricingForBranch($this->branch->id, 'business');
        $defaults = PricingPolicy::getDefaultPricing();

        $this->assertEquals($defaults['courier_percentage'], $pricing['courier_percentage']);
        $this->assertEquals($defaults['branch_percentage'], $pricing['branch_percentage']);
        $this->assertEquals($defaults['km_rate_business'], $pricing['km_rate_business']);
        $this->assertEquals($defaults['km_rate_courier'], $pricing['km_rate_courier']);
        $this->assertEquals($defaults['base_fee'], $pricing['base_fee']);
        $this->assertEquals('Standart Politika', $pricing['policy_name']);
    }

    /** @test */
    public function get_pricing_for_nonexistent_branch_returns_defaults(): void
    {
        $pricing = PricingPolicy::getPricingForBranch(99999, 'business');
        $defaults = PricingPolicy::getDefaultPricing();

        $this->assertEquals($defaults, $pricing);
    }

    /** @test */
    public function default_pricing_has_expected_constants(): void
    {
        $defaults = PricingPolicy::getDefaultPricing();

        $this->assertEquals(0.60, $defaults['courier_percentage']);
        $this->assertEquals(0.40, $defaults['branch_percentage']);
        $this->assertEquals(2.0, $defaults['km_rate_business']);
        $this->assertEquals(1.2, $defaults['km_rate_courier']);
        $this->assertEquals(10.0, $defaults['base_fee']);
        $this->assertEquals('Standart Politika', $defaults['policy_name']);
    }

    // =========================================================================
    // storePricingPolicy - response includes rules
    // =========================================================================

    /** @test */
    public function store_policy_response_includes_loaded_rules(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->postJson(route('bayi.isletme.pricing-policies.store', $this->branch), [
                'type' => 'business',
                'policy_type' => 'fixed',
                'name' => 'Response Test',
                'is_active' => true,
                'rule_price' => 12.50,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'policy' => [
                'id',
                'branch_id',
                'type',
                'policy_type',
                'name',
                'is_active',
                'rules',
            ],
        ]);

        $data = $response->json();
        $this->assertCount(1, $data['policy']['rules']);
        $this->assertEquals(12.50, (float) $data['policy']['rules'][0]['price']);
    }

    // =========================================================================
    // Validation tests
    // =========================================================================

    /** @test */
    public function store_policy_requires_name(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->post(route('bayi.isletme.pricing-policies.store', $this->branch), [
                'type' => 'business',
                'policy_type' => 'fixed',
                'is_active' => true,
            ]);

        $this->assertContains($response->getStatusCode(), [302, 422]);
        $this->assertDatabaseCount('pricing_policies', 0);
    }

    /** @test */
    public function store_policy_requires_valid_type(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->post(route('bayi.isletme.pricing-policies.store', $this->branch), [
                'type' => 'invalid',
                'policy_type' => 'fixed',
                'name' => 'Test',
                'is_active' => true,
            ]);

        $this->assertContains($response->getStatusCode(), [302, 422]);
        $this->assertDatabaseCount('pricing_policies', 0);
    }

    /** @test */
    public function store_policy_requires_valid_policy_type(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->post(route('bayi.isletme.pricing-policies.store', $this->branch), [
                'type' => 'business',
                'policy_type' => 'nonexistent',
                'name' => 'Test',
                'is_active' => true,
            ]);

        $this->assertContains($response->getStatusCode(), [302, 422]);
        $this->assertDatabaseCount('pricing_policies', 0);
    }

    // =========================================================================
    // Ownership - cannot create policy for other bayi's branch
    // =========================================================================

    /** @test */
    public function bayi_cannot_create_pricing_policy_for_other_branch(): void
    {
        $otherBayi = User::factory()->bayi()->create([
            'role' => 'bayi',
            'parent_id' => null,
        ]);
        $otherBranch = Branch::factory()->create(['user_id' => $otherBayi->id]);

        $response = $this->actingAs($this->bayiUser)
            ->postJson(route('bayi.isletme.pricing-policies.store', $otherBranch), [
                'type' => 'business',
                'policy_type' => 'fixed',
                'name' => 'Yetkisiz',
                'is_active' => true,
                'rule_price' => 10,
            ]);

        $response->assertStatus(403);
    }
}
