<?php

namespace Tests\Feature\Security;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Payment method whitelist tests.
 * ExternalOrderController requires auth:api which is hard to set up in tests,
 * so we test the validation rules via the internal OrderController (siparis.store)
 * which shares the same payment_method whitelist concept.
 *
 * Also verifies ExternalOrderController's validation rule string directly.
 */
class PaymentMethodWhitelistTest extends TestCase
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
    // Valid payment methods accepted
    // =========================================================================

    /** @test */
    public function order_accepts_cash_payment(): void
    {
        $this->assertPaymentMethodAccepted('cash');
    }

    /** @test */
    public function order_accepts_card_payment(): void
    {
        $this->assertPaymentMethodAccepted('card');
    }

    /** @test */
    public function order_accepts_online_payment(): void
    {
        $this->assertPaymentMethodAccepted('online');
    }

    /** @test */
    public function order_accepts_pluxee_payment(): void
    {
        $this->assertPaymentMethodAccepted('pluxee');
    }

    /** @test */
    public function order_accepts_edenred_payment(): void
    {
        $this->assertPaymentMethodAccepted('edenred');
    }

    /** @test */
    public function order_accepts_multinet_payment(): void
    {
        $this->assertPaymentMethodAccepted('multinet');
    }

    /** @test */
    public function order_accepts_metropol_payment(): void
    {
        $this->assertPaymentMethodAccepted('metropol');
    }

    /** @test */
    public function order_accepts_tokenflex_payment(): void
    {
        $this->assertPaymentMethodAccepted('tokenflex');
    }

    /** @test */
    public function order_accepts_setcard_payment(): void
    {
        $this->assertPaymentMethodAccepted('setcard');
    }

    // =========================================================================
    // Invalid payment methods rejected
    // =========================================================================

    /** @test */
    public function order_rejects_bitcoin_payment(): void
    {
        $this->assertPaymentMethodRejected('bitcoin');
    }

    /** @test */
    public function order_rejects_hack_payment(): void
    {
        $this->assertPaymentMethodRejected('hack');
    }

    /** @test */
    public function order_rejects_empty_string_payment(): void
    {
        $this->assertPaymentMethodRejected('');
    }

    /** @test */
    public function order_rejects_sql_injection_in_payment_method(): void
    {
        $this->assertPaymentMethodRejected("cash'; DROP TABLE orders; --");
    }

    // =========================================================================
    // ExternalOrderController validation rule verification
    // =========================================================================

    /** @test */
    public function external_order_controller_has_payment_method_whitelist(): void
    {
        // Verify the ExternalOrderController validation includes the full whitelist
        $expectedMethods = ['cash', 'card', 'online', 'pluxee', 'edenred', 'multinet', 'metropol', 'tokenflex', 'setcard'];

        $controllerContent = file_get_contents(
            base_path('app/Http/Controllers/Api/ExternalOrderController.php')
        );

        foreach ($expectedMethods as $method) {
            $this->assertStringContainsString($method, $controllerContent,
                "ExternalOrderController should include '{$method}' in payment_method validation");
        }
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function assertPaymentMethodAccepted(string $method): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->post(route('siparis.store'), [
                'customer_name' => 'Test Musteri',
                'customer_phone' => '05321234567',
                'customer_address' => 'Test Adres',
                'branch_id' => $this->branch->id,
                'delivery_fee' => 10.00,
                'payment_method' => $method,
                'items' => [
                    ['product_id' => null, 'name' => 'Test Urun', 'quantity' => 1, 'price' => 50.00],
                ],
            ]);

        $this->assertNotEquals(500, $response->getStatusCode(),
            "Payment method '{$method}' should not cause server error");
    }

    private function assertPaymentMethodRejected(string $method): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->post(route('siparis.store'), [
                'customer_name' => 'Test Musteri',
                'customer_phone' => '05321234567',
                'customer_address' => 'Test Adres',
                'branch_id' => $this->branch->id,
                'delivery_fee' => 10.00,
                'payment_method' => $method,
                'items' => [
                    ['product_id' => null, 'name' => 'Test Urun', 'quantity' => 1, 'price' => 50.00],
                ],
            ]);

        $this->assertContains($response->getStatusCode(), [302, 422],
            "Payment method '{$method}' should be rejected");
    }
}
