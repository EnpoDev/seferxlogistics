<?php

namespace Tests\Unit\Models;

use App\Models\Branch;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourierTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_status_constants(): void
    {
        $this->assertEquals('available', Courier::STATUS_AVAILABLE);
        $this->assertEquals('busy', Courier::STATUS_BUSY);
        $this->assertEquals('offline', Courier::STATUS_OFFLINE);
        $this->assertEquals('on_break', Courier::STATUS_ON_BREAK);
    }

    /** @test */
    public function it_returns_correct_status_colors(): void
    {
        $available = Courier::factory()->create(['status' => Courier::STATUS_AVAILABLE]);
        $busy = Courier::factory()->create(['status' => Courier::STATUS_BUSY]);
        $offline = Courier::factory()->create(['status' => Courier::STATUS_OFFLINE]);
        $onBreak = Courier::factory()->create(['status' => Courier::STATUS_ON_BREAK]);

        $this->assertEquals('green', $available->getStatusColor());
        $this->assertEquals('orange', $busy->getStatusColor());
        $this->assertEquals('gray', $offline->getStatusColor());
        $this->assertEquals('yellow', $onBreak->getStatusColor());
    }

    /** @test */
    public function it_can_go_online(): void
    {
        $courier = Courier::factory()->create(['status' => Courier::STATUS_OFFLINE]);

        $courier->goOnline();

        $courier->refresh();
        $this->assertEquals(Courier::STATUS_AVAILABLE, $courier->status);
        $this->assertNotNull($courier->last_login_at);
    }

    /** @test */
    public function it_can_go_offline(): void
    {
        $courier = Courier::factory()->create(['status' => Courier::STATUS_AVAILABLE]);

        $courier->goOffline();

        $this->assertEquals(Courier::STATUS_OFFLINE, $courier->fresh()->status);
    }

    /** @test */
    public function it_can_take_break(): void
    {
        $courier = Courier::factory()->create(['status' => Courier::STATUS_AVAILABLE]);

        $courier->takeBreak();

        $this->assertEquals(Courier::STATUS_ON_BREAK, $courier->fresh()->status);
    }

    /** @test */
    public function it_increments_active_orders(): void
    {
        $courier = Courier::factory()->create([
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => 0,
        ]);

        $courier->incrementActiveOrders();

        $this->assertEquals(1, $courier->fresh()->active_orders_count);
    }

    /** @test */
    public function it_becomes_busy_when_reaching_max_orders(): void
    {
        $courier = Courier::factory()->create([
            'status' => Courier::STATUS_AVAILABLE,
            'active_orders_count' => Courier::MAX_ACTIVE_ORDERS - 1,
        ]);

        $courier->incrementActiveOrders();

        $courier->refresh();
        $this->assertEquals(Courier::MAX_ACTIVE_ORDERS, $courier->active_orders_count);
        $this->assertEquals(Courier::STATUS_BUSY, $courier->status);
    }

    /** @test */
    public function it_decrements_active_orders(): void
    {
        $courier = Courier::factory()->create([
            'status' => Courier::STATUS_BUSY,
            'active_orders_count' => 3,
        ]);

        $courier->decrementActiveOrders();

        $courier->refresh();
        $this->assertEquals(2, $courier->active_orders_count);
        $this->assertEquals(Courier::STATUS_AVAILABLE, $courier->status);
    }

    /** @test */
    public function it_records_delivery_and_updates_stats(): void
    {
        $courier = Courier::factory()->create([
            'total_deliveries' => 0,
            'average_delivery_time' => 0,
        ]);

        $courier->recordDelivery(30);

        $courier->refresh();
        $this->assertEquals(1, $courier->total_deliveries);
        $this->assertEquals(30.0, $courier->average_delivery_time);
    }

    /** @test */
    public function it_calculates_average_delivery_time_correctly(): void
    {
        $courier = Courier::factory()->create([
            'total_deliveries' => 1,
            'average_delivery_time' => 20,
        ]);

        $courier->recordDelivery(40);

        $courier->refresh();
        $this->assertEquals(2, $courier->total_deliveries);
        $this->assertEquals(30.0, $courier->average_delivery_time); // (20 + 40) / 2
    }

    /** @test */
    public function it_calculates_distance_using_haversine(): void
    {
        $courier = Courier::factory()->create([
            'lat' => 41.015137,
            'lng' => 28.979530,
        ]);

        // A location about 1km away
        $distance = $courier->calculateDistanceTo(41.023, 28.979);

        $this->assertGreaterThan(500, $distance); // At least 500m
        $this->assertLessThan(2000, $distance); // Less than 2km
    }

    /** @test */
    public function it_returns_max_distance_when_no_location(): void
    {
        $courier = Courier::factory()->create([
            'lat' => null,
            'lng' => null,
        ]);

        $distance = $courier->calculateDistanceTo(41.0, 29.0);

        $this->assertEquals(PHP_FLOAT_MAX, $distance);
    }

    /** @test */
    public function it_checks_if_on_shift_when_no_shift_defined(): void
    {
        $courier = Courier::factory()->create([
            'shift_start' => null,
            'shift_end' => null,
        ]);

        $this->assertTrue($courier->isOnShift());
    }

    /** @test */
    public function it_checks_notification_capability(): void
    {
        $courierWithNotifications = Courier::factory()->create([
            'notification_enabled' => true,
            'shift_start' => null,
            'shift_end' => null,
        ]);

        $courierWithoutNotifications = Courier::factory()->create([
            'notification_enabled' => false,
        ]);

        $this->assertTrue($courierWithNotifications->canReceiveNotification());
        $this->assertFalse($courierWithoutNotifications->canReceiveNotification());
    }

    /** @test */
    public function it_checks_app_access(): void
    {
        $courierWithAccess = Courier::factory()->create([
            'is_app_enabled' => true,
            'password' => 'hashed_password',
        ]);

        $courierWithoutAccess = Courier::factory()->create([
            'is_app_enabled' => false,
        ]);

        $courierWithoutPassword = Courier::factory()->create([
            'is_app_enabled' => true,
            'password' => null,
        ]);

        $this->assertTrue($courierWithAccess->hasAppAccess());
        $this->assertFalse($courierWithoutAccess->hasAppAccess());
        $this->assertFalse($courierWithoutPassword->hasAppAccess());
    }

    /** @test */
    public function it_returns_tier_labels(): void
    {
        $bronze = Courier::factory()->create(['tier' => 'bronze']);
        $silver = Courier::factory()->create(['tier' => 'silver']);
        $gold = Courier::factory()->create(['tier' => 'gold']);
        $platinum = Courier::factory()->create(['tier' => 'platinum']);

        $this->assertEquals('Bronz', $bronze->getTierLabel());
        $this->assertEquals('Gümüş', $silver->getTierLabel());
        $this->assertEquals('Altın', $gold->getTierLabel());
        $this->assertEquals('Platin', $platinum->getTierLabel());
    }

    /** @test */
    public function it_returns_tier_colors(): void
    {
        $gold = Courier::factory()->create(['tier' => 'gold']);

        $this->assertEquals('#FFD700', $gold->getTierColor());
    }

    /** @test */
    public function it_returns_work_type_labels(): void
    {
        $fullTime = Courier::factory()->create(['work_type' => 'full_time']);
        $partTime = Courier::factory()->create(['work_type' => 'part_time']);
        $freelance = Courier::factory()->create(['work_type' => 'freelance']);

        $this->assertEquals('Tam Zamanlı', $fullTime->getWorkTypeLabel());
        $this->assertEquals('Yarı Zamanlı', $partTime->getWorkTypeLabel());
        $this->assertEquals('Serbest', $freelance->getWorkTypeLabel());
    }

    /** @test */
    public function it_returns_platform_labels(): void
    {
        $android = Courier::factory()->create(['platform' => 'android']);
        $ios = Courier::factory()->create(['platform' => 'ios']);

        $this->assertEquals('Android', $android->getPlatformLabel());
        $this->assertEquals('iOS', $ios->getPlatformLabel());
    }

    /** @test */
    public function it_can_set_and_get_break_for_day(): void
    {
        $courier = Courier::factory()->create(['break_durations' => null]);

        $courier->setBreakForDay(1, 60, 2); // Monday, 60 min, 2 parts

        $break = $courier->fresh()->getBreakForDay(1);

        $this->assertNotNull($break);
        $this->assertEquals(60, $break['duration']);
        $this->assertEquals(2, $break['parts']);
    }

    /** @test */
    public function it_returns_null_for_undefined_break(): void
    {
        $courier = Courier::factory()->create(['break_durations' => null]);

        $this->assertNull($courier->getBreakForDay(5));
    }

    /** @test */
    public function it_checks_if_has_template_shift(): void
    {
        $courierWithShifts = Courier::factory()->create([
            'shifts' => ['monday' => ['start' => '09:00', 'end' => '17:00']],
        ]);

        $courierWithoutShifts = Courier::factory()->create([
            'shifts' => null,
        ]);

        $this->assertTrue($courierWithShifts->hasTemplateShift());
        $this->assertFalse($courierWithoutShifts->hasTemplateShift());
    }

    /** @test */
    public function available_scope_returns_only_available_couriers(): void
    {
        $available = Courier::factory()->create(['status' => Courier::STATUS_AVAILABLE]);
        $busy = Courier::factory()->create(['status' => Courier::STATUS_BUSY]);
        $offline = Courier::factory()->create(['status' => Courier::STATUS_OFFLINE]);

        $result = Courier::available()->get();

        $this->assertTrue($result->contains($available));
        $this->assertFalse($result->contains($busy));
        $this->assertFalse($result->contains($offline));
    }

    /** @test */
    public function app_enabled_scope_returns_enabled_couriers(): void
    {
        $enabled = Courier::factory()->create(['is_app_enabled' => true]);
        $disabled = Courier::factory()->create(['is_app_enabled' => false]);

        $result = Courier::appEnabled()->get();

        $this->assertTrue($result->contains($enabled));
        $this->assertFalse($result->contains($disabled));
    }

    /** @test */
    public function it_has_orders_relationship(): void
    {
        $branch = Branch::factory()->create();
        $customer = Customer::factory()->create();
        $courier = Courier::factory()->create();

        Order::factory()->count(3)->create([
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'courier_id' => $courier->id,
        ]);

        $this->assertCount(3, $courier->orders);
    }

    /** @test */
    public function it_has_active_orders_relationship(): void
    {
        $branch = Branch::factory()->create();
        $customer = Customer::factory()->create();
        $courier = Courier::factory()->create();

        // Active orders
        Order::factory()->create([
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'courier_id' => $courier->id,
            'status' => Order::STATUS_ON_DELIVERY,
        ]);

        // Completed order
        Order::factory()->create([
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'courier_id' => $courier->id,
            'status' => Order::STATUS_DELIVERED,
        ]);

        $this->assertCount(1, $courier->activeOrders);
    }
}
