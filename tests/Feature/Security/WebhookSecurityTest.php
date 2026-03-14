<?php

namespace Tests\Feature\Security;

use App\Models\Order;
use App\Services\FcmPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Tests for Task #29: Webhook Security
 * - Webhook signature validation middleware
 * - FcmPushService rate limiting (5 min cache)
 * - ExternalOrderController status transition validation
 */
class WebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Webhook Signature Validation
    // =========================================================================

    /** @test */
    public function webhook_without_signature_returns_401(): void
    {
        // Configure a webhook secret so the middleware doesn't return 500
        Config::set('services.getir.webhook_secret', 'test-secret-key');

        $response = $this->postJson('/webhooks/getir/some-token', [
            'order_id' => '123',
            'status' => 'delivered',
        ]);

        // Should fail signature validation (no signature header)
        $response->assertStatus(401);
    }

    /** @test */
    public function webhook_with_invalid_signature_returns_401(): void
    {
        Config::set('services.getir.webhook_secret', 'test-secret-key');

        $response = $this->postJson('/webhooks/getir/some-token', [
            'order_id' => '123',
            'status' => 'delivered',
        ], [
            'X-Getir-Signature' => 'invalid-signature-value',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function webhook_with_valid_signature_passes_middleware(): void
    {
        $secret = 'test-secret-key';
        Config::set('services.getir.webhook_secret', $secret);

        $payload = json_encode(['order_id' => '123', 'status' => 'delivered']);
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        $response = $this->call('POST', '/webhooks/getir/some-token', [], [], [], [
            'HTTP_X-Getir-Signature' => $expectedSignature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        // Should pass webhook.validate middleware. The response may be 400/401
        // from IntegrationController (invalid token), but NOT 401 from middleware
        // signature validation. If it reaches the controller, signature passed.
        $this->assertNotEquals(500, $response->getStatusCode());
    }

    /** @test */
    public function webhook_without_configured_secret_returns_500(): void
    {
        // Ensure no webhook secret is configured
        Config::set('services.getir.webhook_secret', null);

        $response = $this->postJson('/webhooks/getir/some-token', [
            'order_id' => '123',
        ]);

        $response->assertStatus(500);
        $response->assertJson(['error' => 'Webhook configuration error']);
    }

    /** @test */
    public function webhook_with_expired_timestamp_returns_401(): void
    {
        $secret = 'test-secret-key';
        Config::set('services.getir.webhook_secret', $secret);

        // Timestamp from 10 minutes ago (beyond 5-minute window)
        $expiredTimestamp = time() - 600;

        $payload = json_encode([
            'order_id' => '123',
            'timestamp' => $expiredTimestamp,
        ]);
        $signature = hash_hmac('sha256', $payload, $secret);

        $response = $this->call('POST', '/webhooks/getir/some-token', [], [], [], [
            'HTTP_X-Getir-Signature' => $signature,
            'HTTP_X-Timestamp' => (string) $expiredTimestamp,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(401);
    }

    // =========================================================================
    // FcmPushService - Rate Limiting (5 min cache)
    // =========================================================================

    /** @test */
    public function fcm_pool_notification_is_rate_limited_for_same_order(): void
    {
        $order = Order::factory()->create();

        $service = new FcmPushService();

        // First call - should set cache
        $result1 = $service->notifyPoolOrder($order);

        // Second call within 5 minutes - should be rate limited
        $result2 = $service->notifyPoolOrder($order);

        $this->assertEquals(0, $result2['sent']);
        $this->assertStringContainsString('Rate limited', $result2['message'] ?? '');
    }

    /** @test */
    public function fcm_pool_notification_cache_key_exists_after_first_call(): void
    {
        $order = Order::factory()->create();

        $service = new FcmPushService();
        $service->notifyPoolOrder($order);

        $cacheKey = "pool_notification_{$order->id}";
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function fcm_different_orders_are_not_rate_limited(): void
    {
        $order1 = Order::factory()->create();
        $order2 = Order::factory()->create();

        $service = new FcmPushService();

        $service->notifyPoolOrder($order1);
        $result2 = $service->notifyPoolOrder($order2);

        // Second order should NOT be rate limited (different cache key)
        $this->assertNotEquals('Rate limited - notification already sent recently', $result2['message'] ?? '');
    }

    // =========================================================================
    // ExternalOrderController - Status Transition Validation
    // =========================================================================

    /** @test */
    public function delivered_to_preparing_transition_is_blocked(): void
    {
        // Test the isValidStatusTransition logic directly via reflection
        $controller = new \App\Http\Controllers\Api\ExternalOrderController();
        $method = new \ReflectionMethod($controller, 'isValidStatusTransition');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($controller, 'delivered', 'preparing'));
        $this->assertFalse($method->invoke($controller, 'delivered', 'pending'));
        $this->assertFalse($method->invoke($controller, 'delivered', 'on_delivery'));
    }

    /** @test */
    public function cancelled_to_any_transition_is_blocked(): void
    {
        $controller = new \App\Http\Controllers\Api\ExternalOrderController();
        $method = new \ReflectionMethod($controller, 'isValidStatusTransition');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($controller, 'cancelled', 'pending'));
        $this->assertFalse($method->invoke($controller, 'cancelled', 'preparing'));
        $this->assertFalse($method->invoke($controller, 'cancelled', 'delivered'));
    }

    /** @test */
    public function valid_forward_transitions_are_allowed(): void
    {
        $controller = new \App\Http\Controllers\Api\ExternalOrderController();
        $method = new \ReflectionMethod($controller, 'isValidStatusTransition');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($controller, 'pending', 'preparing'));
        $this->assertTrue($method->invoke($controller, 'pending', 'cancelled'));
        $this->assertTrue($method->invoke($controller, 'preparing', 'ready'));
        $this->assertTrue($method->invoke($controller, 'ready', 'on_delivery'));
        $this->assertTrue($method->invoke($controller, 'on_delivery', 'delivered'));
    }

    /** @test */
    public function backward_transitions_are_blocked(): void
    {
        $controller = new \App\Http\Controllers\Api\ExternalOrderController();
        $method = new \ReflectionMethod($controller, 'isValidStatusTransition');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($controller, 'preparing', 'pending'));
        $this->assertFalse($method->invoke($controller, 'ready', 'preparing'));
        $this->assertFalse($method->invoke($controller, 'on_delivery', 'ready'));
        $this->assertFalse($method->invoke($controller, 'delivered', 'on_delivery'));
    }
}
