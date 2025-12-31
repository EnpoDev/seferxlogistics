<?php

namespace Tests\Feature;

use App\Helpers\PrivacyHelper;
use App\Models\Courier;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guvenlik ve KVKK Uyumluluk Testleri
 *
 * Bu testler asagidaki kontrolleri yapar:
 * 1. Yetkilendirme kontrolleri
 * 2. PII maskeleme
 * 3. Rate limiting
 * 4. Security header'lar
 *
 * @package Tests\Feature
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // YETKILENDIRME TESTLERI
    // =========================================================================

    public function test_unauthenticated_user_cannot_access_api_couriers(): void
    {
        $response = $this->getJson('/api/couriers/search');

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_access_api_orders(): void
    {
        $response = $this->getJson('/api/orders/search');

        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_access_map_data(): void
    {
        $response = $this->getJson('/api/map-data');

        $response->assertStatus(401);
    }

    public function test_user_without_role_cannot_access_courier_data(): void
    {
        $user = User::factory()->create(['roles' => []]);

        $response = $this->actingAs($user)->getJson('/api/couriers/search');

        $response->assertStatus(403);
    }

    public function test_bayi_user_can_access_courier_data(): void
    {
        $user = User::factory()->create(['roles' => ['bayi']]);

        $response = $this->actingAs($user)->getJson('/api/couriers/search');

        $response->assertStatus(200);
    }

    public function test_isletme_user_can_access_order_data(): void
    {
        $user = User::factory()->create(['roles' => ['isletme']]);

        $response = $this->actingAs($user)->getJson('/api/orders/search');

        $response->assertStatus(200);
    }

    // =========================================================================
    // PII MASKELEME TESTLERI
    // =========================================================================

    public function test_phone_is_masked_in_courier_list(): void
    {
        $user = User::factory()->create(['roles' => ['isletme']]);
        Courier::factory()->create([
            'phone' => '05321234567',
            'lat' => 41.0,
            'lng' => 29.0,
        ]);

        $response = $this->actingAs($user)->getJson('/api/couriers/search');

        $response->assertStatus(200);
        $response->assertJsonMissing(['phone' => '05321234567']);
        // Maskelenmis format kontrolu
        $data = $response->json();
        if (!empty($data)) {
            $this->assertStringContainsString('*', $data[0]['phone']);
        }
    }

    public function test_tc_no_is_not_exposed_in_api(): void
    {
        $user = User::factory()->create(['roles' => ['bayi']]);
        $courier = Courier::factory()->create([
            'tc_no' => '12345678901',
            'lat' => 41.0,
            'lng' => 29.0,
        ]);

        $response = $this->actingAs($user)->getJson("/api/couriers/{$courier->id}");

        $response->assertStatus(200);
        $response->assertJsonMissingPath('tc_no');
    }

    public function test_customer_address_is_masked_in_order_list(): void
    {
        $user = User::factory()->create(['roles' => ['bayi']]);
        Order::factory()->create([
            'customer_address' => 'Ataturk Mah. 123 Sok. No:5 Kadikoy/Istanbul',
            'lat' => 41.0,
            'lng' => 29.0,
        ]);

        $response = $this->actingAs($user)->getJson('/api/orders/search');

        $response->assertStatus(200);
        $response->assertJsonMissing(['customer_address' => 'Ataturk Mah. 123 Sok. No:5 Kadikoy/Istanbul']);
    }

    // =========================================================================
    // PRIVACY HELPER UNIT TESTLERI
    // =========================================================================

    public function test_privacy_helper_masks_phone_correctly(): void
    {
        $masked = PrivacyHelper::maskPhone('05321234567');

        $this->assertStringContainsString('*', $masked);
        $this->assertStringEndsWith('67', $masked);
        $this->assertStringNotContainsString('321234', $masked);
    }

    public function test_privacy_helper_masks_tc_no_correctly(): void
    {
        $masked = PrivacyHelper::maskTcNo('12345678901');

        $this->assertEquals('123******01', $masked);
    }

    public function test_privacy_helper_masks_email_correctly(): void
    {
        $masked = PrivacyHelper::maskEmail('john.doe@example.com');

        $this->assertStringContainsString('@', $masked);
        $this->assertStringContainsString('*', $masked);
        $this->assertStringStartsWith('j', $masked);
    }

    public function test_privacy_helper_masks_iban_correctly(): void
    {
        $masked = PrivacyHelper::maskIban('TR123456789012345678901234');

        $this->assertStringStartsWith('TR12', $masked);
        $this->assertStringContainsString('*', $masked);
    }

    public function test_privacy_helper_masks_ip_address_correctly(): void
    {
        $masked = PrivacyHelper::maskIpAddress('192.168.1.100');

        $this->assertEquals('192.168.*.*', $masked);
    }

    public function test_privacy_helper_sanitizes_log_data(): void
    {
        $data = [
            'customer_name' => 'Ahmet Yilmaz',
            'customer_phone' => '05321234567',
            'password' => 'secret123',
            'tc_no' => '12345678901',
            'order_id' => 123,
        ];

        $sanitized = PrivacyHelper::sanitizeForLogging($data);

        $this->assertEquals(123, $sanitized['order_id']); // Hassas olmayan veri korunur
        $this->assertStringContainsString('*', $sanitized['customer_phone']);
        $this->assertEquals('[REDACTED]', $sanitized['password']);
    }

    // =========================================================================
    // SECURITY HEADER TESTLERI
    // =========================================================================

    public function test_security_headers_are_present(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    // =========================================================================
    // INPUT VALIDATION TESTLERI
    // =========================================================================

    public function test_api_search_validates_input_length(): void
    {
        $user = User::factory()->create(['roles' => ['bayi']]);

        // 100 karakterden uzun arama sorgusu
        $longQuery = str_repeat('a', 150);

        $response = $this->actingAs($user)->getJson("/api/couriers/search?q={$longQuery}");

        $response->assertStatus(422); // Validation error
    }

    public function test_api_search_validates_status_values(): void
    {
        $user = User::factory()->create(['roles' => ['bayi']]);

        $response = $this->actingAs($user)->getJson('/api/couriers/search?status=invalid_status');

        $response->assertStatus(422); // Validation error
    }

    // =========================================================================
    // RATE LIMITING TESTLERI
    // =========================================================================

    public function test_api_is_rate_limited(): void
    {
        $user = User::factory()->create(['roles' => ['bayi']]);

        // 60 istek yap
        for ($i = 0; $i < 60; $i++) {
            $this->actingAs($user)->getJson('/api/couriers/search');
        }

        // 61. istek rate limited olmali
        $response = $this->actingAs($user)->getJson('/api/couriers/search');

        $response->assertStatus(429); // Too Many Requests
    }
}
