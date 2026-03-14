<?php

namespace Tests\Feature\Security;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Tests for Task #27: Rate Limiting
 * - POST /siparis throttle:30,1 -> 31st request returns 429
 * - POST /musteri throttle:30,1 -> 31st request returns 429
 */
class RateLimitingTest extends TestCase
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

        // Clear rate limiter before each test
        RateLimiter::clear('siparis.store');
    }

    // =========================================================================
    // Siparis store rate limiting (throttle:30,1)
    // =========================================================================

    /** @test */
    public function siparis_store_returns_429_after_30_requests(): void
    {
        $this->actingAs($this->bayiUser);

        // Send 30 requests (all should be accepted or validation-rejected, but not throttled)
        for ($i = 1; $i <= 30; $i++) {
            $response = $this->post(route('siparis.store'), [
                'customer_name' => 'Test Musteri ' . $i,
                'customer_phone' => '0532123456' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'customer_address' => 'Test Adres',
                'branch_id' => $this->branch->id,
                'delivery_fee' => 10.00,
                'payment_method' => 'cash',
                'items' => [
                    ['product_id' => null, 'name' => 'Test Urun', 'quantity' => 1, 'price' => 50.00],
                ],
            ]);

            $this->assertNotEquals(429, $response->getStatusCode(),
                "Request #{$i} should not be throttled (limit is 30)");
        }

        // 31st request should be throttled
        $response = $this->post(route('siparis.store'), [
            'customer_name' => 'Throttled Musteri',
            'customer_phone' => '05321234599',
            'customer_address' => 'Test Adres',
            'branch_id' => $this->branch->id,
            'delivery_fee' => 10.00,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => null, 'name' => 'Test Urun', 'quantity' => 1, 'price' => 50.00],
            ],
        ]);

        $response->assertStatus(429);
    }

    // =========================================================================
    // Musteri store rate limiting (throttle:30,1)
    // =========================================================================

    /** @test */
    public function musteri_store_returns_429_after_30_requests(): void
    {
        $this->actingAs($this->bayiUser);

        // Send 30 requests
        for ($i = 1; $i <= 30; $i++) {
            $response = $this->post(route('musteri.store'), [
                'name' => 'Test Musteri ' . $i,
                'phone' => '0532100' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'address' => 'Test Adres',
            ]);

            $this->assertNotEquals(429, $response->getStatusCode(),
                "Request #{$i} should not be throttled (limit is 30)");
        }

        // 31st request should be throttled
        $response = $this->post(route('musteri.store'), [
            'name' => 'Throttled Musteri',
            'phone' => '05321009999',
            'address' => 'Test Adres',
        ]);

        $response->assertStatus(429);
    }

    // =========================================================================
    // Rate limit resets after time window
    // =========================================================================

    /** @test */
    public function rate_limit_headers_are_present(): void
    {
        $this->actingAs($this->bayiUser);

        $response = $this->post(route('siparis.store'), [
            'customer_name' => 'Test',
            'customer_phone' => '05321234567',
            'customer_address' => 'Test Adres',
            'branch_id' => $this->branch->id,
            'delivery_fee' => 10.00,
            'payment_method' => 'cash',
            'items' => [
                ['product_id' => null, 'name' => 'Test', 'quantity' => 1, 'price' => 50.00],
            ],
        ]);

        // Rate limit headers should be present
        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
    }
}
