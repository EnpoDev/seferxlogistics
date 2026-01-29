<?php

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\BranchSetting;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Services\CustomerNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CustomerNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CustomerNotificationService $notificationService;
    protected Branch $branch;
    protected BranchSetting $settings;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationService = new CustomerNotificationService();

        $this->branch = Branch::factory()->create([
            'name' => 'Test Branch',
        ]);

        // Create branch settings with notifications enabled
        $this->settings = BranchSetting::create([
            'branch_id' => $this->branch->id,
            'customer_sms_enabled' => false, // Disabled to prevent actual API calls
            'customer_whatsapp_enabled' => false,
            'notify_on_confirmed' => true,
            'notify_on_preparing' => true,
            'notify_on_ready' => true,
            'notify_on_courier_assigned' => true,
            'notify_on_picked_up' => true,
            'notify_on_delivered' => true,
            'notify_on_cancelled' => true,
        ]);
    }

    /** @test */
    public function it_sends_sms_notification(): void
    {
        Log::spy();

        $order = $this->createOrder([
            'customer_phone' => '5551234567',
            'tracking_token' => 'test-token-123',
        ]);

        $result = $this->notificationService->sendSMS($order, 'order_confirmed');

        // Without actual SMS provider configured, should log and return true
        $this->assertTrue($result);

        // Verify notification was logged
        $this->assertDatabaseHas('notification_logs', [
            'order_id' => $order->id,
            'channel' => 'sms',
            'template' => 'order_confirmed',
        ]);
    }

    /** @test */
    public function it_sends_whatsapp_notification(): void
    {
        Log::spy();

        $order = $this->createOrder([
            'customer_phone' => '5551234567',
            'tracking_token' => 'test-token-123',
        ]);

        $result = $this->notificationService->sendWhatsApp($order, 'order_confirmed');

        // Without actual WhatsApp provider configured, should log and return true
        $this->assertTrue($result);

        // Verify notification was logged
        $this->assertDatabaseHas('notification_logs', [
            'order_id' => $order->id,
            'channel' => 'whatsapp',
            'template' => 'order_confirmed',
        ]);
    }

    /** @test */
    public function it_returns_false_when_no_phone_number(): void
    {
        $order = $this->createOrder([
            'customer_phone' => '',
        ]);

        $result = $this->notificationService->sendSMS($order, 'order_confirmed');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_returns_false_for_invalid_template(): void
    {
        $order = $this->createOrder([
            'customer_phone' => '5551234567',
        ]);

        $result = $this->notificationService->sendSMS($order, 'invalid_template');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_logs_notification_on_send(): void
    {
        $order = $this->createOrder([
            'customer_phone' => '5551234567',
            'tracking_token' => 'test-token-123',
        ]);

        $this->notificationService->sendSMS($order, 'order_confirmed');

        $log = NotificationLog::where('order_id', $order->id)->first();

        $this->assertNotNull($log);
        $this->assertEquals('sms', $log->channel);
        $this->assertEquals('order_confirmed', $log->template);
        $this->assertEquals('5551234567', $log->phone);
        $this->assertNotNull($log->message);
        $this->assertEquals('sent', $log->status);
    }

    /** @test */
    public function it_formats_10_digit_phone_numbers(): void
    {
        $order = $this->createOrder([
            'customer_phone' => '5551234567', // 10 digits
            'tracking_token' => 'test-token',
        ]);

        $this->notificationService->sendSMS($order, 'order_confirmed');

        $log = NotificationLog::where('order_id', $order->id)->first();

        // Phone should be stored as provided
        $this->assertEquals('5551234567', $log->phone);
    }

    /** @test */
    public function it_parses_template_variables_correctly(): void
    {
        $courier = Courier::factory()->create(['name' => 'Test Courier']);

        $order = $this->createOrder([
            'customer_name' => 'John Doe',
            'customer_phone' => '5551234567',
            'order_number' => 'ORD-12345',
            'tracking_token' => 'abc123',
            'courier_id' => $courier->id,
        ]);

        $this->notificationService->sendSMS($order, 'courier_assigned');

        $log = NotificationLog::where('order_id', $order->id)->first();

        // Check that template variables were replaced
        $this->assertStringContainsString('Test Courier', $log->message);
    }

    /** @test */
    public function it_handles_different_notification_templates(): void
    {
        $order = $this->createOrder([
            'customer_phone' => '5551234567',
            'tracking_token' => 'test-token',
        ]);

        $templates = [
            'order_confirmed',
            'order_preparing',
            'order_ready',
            'order_picked_up',
            'order_delivered',
            'order_cancelled',
        ];

        foreach ($templates as $template) {
            $result = $this->notificationService->sendSMS($order, $template);
            $this->assertTrue($result, "Template {$template} should succeed");
        }

        // Verify all were logged
        $this->assertEquals(count($templates), NotificationLog::where('order_id', $order->id)->count());
    }

    /** @test */
    public function it_handles_status_notification_based_on_settings(): void
    {
        // Enable SMS notifications
        $this->settings->update([
            'customer_sms_enabled' => true,
            'notify_on_confirmed' => true,
        ]);

        $order = $this->createOrder([
            'customer_phone' => '5551234567',
            'tracking_token' => 'test-token',
        ]);

        // This tests sendStatusNotification method
        $this->notificationService->sendStatusNotification($order, 'pending');

        // Since notify_on_confirmed is true and sms_enabled is true, should have logged
        $this->assertDatabaseHas('notification_logs', [
            'order_id' => $order->id,
            'template' => 'order_confirmed',
        ]);
    }

    /** @test */
    public function it_respects_notification_disabled_settings(): void
    {
        // Disable notifications for preparing status
        $this->settings->update([
            'customer_sms_enabled' => true,
            'notify_on_preparing' => false,
        ]);

        $order = $this->createOrder([
            'customer_phone' => '5551234567',
            'tracking_token' => 'test-token',
        ]);

        $this->notificationService->sendStatusNotification($order, 'preparing');

        // Should NOT have logged since notify_on_preparing is false
        $this->assertDatabaseMissing('notification_logs', [
            'order_id' => $order->id,
            'template' => 'order_preparing',
        ]);
    }

    /** @test */
    public function it_handles_order_with_courier_info(): void
    {
        $courier = Courier::factory()->create([
            'name' => 'Ali Yilmaz',
            'phone' => '5559876543',
        ]);

        $order = $this->createOrder([
            'customer_phone' => '5551234567',
            'tracking_token' => 'test-token',
            'courier_id' => $courier->id,
        ]);

        $this->notificationService->sendSMS($order, 'courier_assigned');

        $log = NotificationLog::where('order_id', $order->id)->first();

        $this->assertStringContainsString('Ali Yilmaz', $log->message);
    }

    /** @test */
    public function it_handles_cancelled_order_notification(): void
    {
        $order = $this->createOrder([
            'customer_phone' => '5551234567',
            'tracking_token' => 'test-token',
            'cancel_reason' => 'Customer request',
        ]);

        $this->notificationService->sendWhatsApp($order, 'order_cancelled');

        $log = NotificationLog::where('order_id', $order->id)->first();

        $this->assertEquals('whatsapp', $log->channel);
        $this->assertEquals('order_cancelled', $log->template);
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
            'customer_name' => 'Test Customer',
            'customer_phone' => '5551234567',
            'status' => 'pending',
        ], $attributes));
    }
}
