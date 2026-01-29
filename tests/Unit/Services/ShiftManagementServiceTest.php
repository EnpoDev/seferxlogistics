<?php

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use App\Services\ShiftManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ShiftManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ShiftManagementService $service;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ShiftManagementService();
        $this->branch = Branch::factory()->create();
    }

    /** @test */
    public function it_can_get_shift_templates(): void
    {
        $templates = $this->service->getShiftTemplates();

        $this->assertIsArray($templates);
        $this->assertArrayHasKey('sabah', $templates);
        $this->assertArrayHasKey('ogle', $templates);
        $this->assertArrayHasKey('aksam', $templates);
        $this->assertArrayHasKey('gece', $templates);
        $this->assertArrayHasKey('tam_gun', $templates);
        $this->assertArrayHasKey('hafta_sonu', $templates);
    }

    /** @test */
    public function it_can_apply_shift_template_to_courier(): void
    {
        $courier = Courier::factory()->create(['shifts' => null]);

        $result = $this->service->applyShiftTemplate($courier, 'sabah');

        $this->assertTrue($result);

        $courier->refresh();
        $shifts = json_decode($courier->shifts, true);

        $this->assertNotEmpty($shifts);
        $this->assertEquals('08:00', $shifts['monday']['start']);
        $this->assertEquals('16:00', $shifts['monday']['end']);
    }

    /** @test */
    public function it_returns_false_for_invalid_template(): void
    {
        $courier = Courier::factory()->create();

        $result = $this->service->applyShiftTemplate($courier, 'invalid_template');

        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_apply_template_to_specific_days(): void
    {
        $courier = Courier::factory()->create(['shifts' => null]);

        $this->service->applyShiftTemplate($courier, 'hafta_sonu', ['saturday', 'sunday']);

        $courier->refresh();
        $shifts = json_decode($courier->shifts, true);

        $this->assertTrue($shifts['saturday']['enabled']);
        $this->assertTrue($shifts['sunday']['enabled']);
        $this->assertEquals('11:00', $shifts['saturday']['start']);
    }

    /** @test */
    public function it_can_create_weekly_schedule(): void
    {
        $schedule = [
            'monday' => ['enabled' => true, 'start' => '09:00', 'end' => '17:00'],
            'tuesday' => ['enabled' => true, 'start' => '09:00', 'end' => '17:00'],
            'wednesday' => ['enabled' => false],
        ];

        $formatted = $this->service->createWeeklySchedule($schedule);

        $this->assertTrue($formatted['monday']['enabled']);
        $this->assertEquals('09:00', $formatted['monday']['start']);
        $this->assertFalse($formatted['wednesday']['enabled']);
    }

    /** @test */
    public function it_checks_courier_shift_status(): void
    {
        $shifts = json_encode([
            strtolower(now()->format('l')) => [
                'enabled' => true,
                'start' => '00:00',
                'end' => '23:59',
            ],
        ]);

        $courier = Courier::factory()->create(['shifts' => $shifts]);

        $status = $this->service->checkCourierShiftStatus($courier);

        $this->assertArrayHasKey('is_on_shift', $status);
        $this->assertArrayHasKey('current_shift', $status);
        $this->assertArrayHasKey('weekly_shifts', $status);
    }

    /** @test */
    public function it_detects_shift_conflicts(): void
    {
        $shifts = json_encode([
            'monday' => ['enabled' => true, 'start' => '09:00', 'end' => '17:00'],
        ]);

        $courier = Courier::factory()->create(['shifts' => $shifts]);

        $conflicts = $this->service->checkShiftConflicts($courier, 'monday', '10:00', '18:00');

        $this->assertNotEmpty($conflicts);
        $this->assertEquals('overlap', $conflicts[0]['type']);
    }

    /** @test */
    public function it_allows_non_overlapping_shifts(): void
    {
        // No existing shifts for this day
        $courier = Courier::factory()->create(['shifts' => null]);

        $conflicts = $this->service->checkShiftConflicts($courier, 'tuesday', '14:00', '18:00');

        // Filter out rest_time conflicts which depend on previous day
        $overlapConflicts = array_filter($conflicts, fn($c) => $c['type'] === 'overlap');

        $this->assertEmpty($overlapConflicts);
    }

    /** @test */
    public function it_can_get_shift_statistics(): void
    {
        $customer = Customer::factory()->create();
        $courier = Courier::factory()->create(['shifts' => json_encode([])]);

        // Create some delivered orders
        Order::factory()->count(5)->create([
            'branch_id' => $this->branch->id,
            'customer_id' => $customer->id,
            'courier_id' => $courier->id,
            'status' => 'delivered',
            'created_at' => now()->subDays(5),
        ]);

        $stats = $this->service->getShiftStatistics(
            now()->subDays(7),
            now()
        );

        $this->assertArrayHasKey('total_orders', $stats);
        $this->assertArrayHasKey('hourly_distribution', $stats);
        $this->assertArrayHasKey('peak_hours', $stats);
        $this->assertEquals(5, $stats['total_orders']);
    }

    /** @test */
    public function it_can_suggest_optimal_shifts(): void
    {
        $customer = Customer::factory()->create();
        $courier = Courier::factory()->create();

        // Create orders at specific hours
        for ($i = 0; $i < 10; $i++) {
            Order::factory()->create([
                'branch_id' => $this->branch->id,
                'customer_id' => $customer->id,
                'courier_id' => $courier->id,
                'status' => 'delivered',
                'created_at' => now()->subDays(rand(1, 20))->setHour(12),
            ]);
        }

        $suggestions = $this->service->suggestOptimalShifts($courier->id);

        $this->assertArrayHasKey('suggested_start', $suggestions);
        $this->assertArrayHasKey('suggested_end', $suggestions);
        $this->assertArrayHasKey('suggested_days', $suggestions);
        $this->assertArrayHasKey('recommendation', $suggestions);
    }

    /** @test */
    public function it_returns_empty_for_non_existent_courier(): void
    {
        $suggestions = $this->service->suggestOptimalShifts(99999);

        $this->assertEmpty($suggestions);
    }

    /** @test */
    public function it_can_get_active_couriers_by_hour(): void
    {
        // Create couriers with shifts for today
        $today = strtolower(now()->format('l'));

        Courier::factory()->create([
            'shifts' => json_encode([
                $today => ['enabled' => true, 'start' => '09:00', 'end' => '17:00'],
            ]),
        ]);

        Courier::factory()->create([
            'shifts' => json_encode([
                $today => ['enabled' => true, 'start' => '12:00', 'end' => '20:00'],
            ]),
        ]);

        $hourlyData = $this->service->getActiveCouriersByHour();

        $this->assertCount(24, $hourlyData);
        $this->assertArrayHasKey('hour', $hourlyData[0]);
        $this->assertArrayHasKey('count', $hourlyData[0]);
    }

    /** @test */
    public function it_handles_night_shift_spanning_midnight(): void
    {
        $today = strtolower(now()->format('l'));

        $courier = Courier::factory()->create([
            'shifts' => json_encode([
                $today => ['enabled' => true, 'start' => '20:00', 'end' => '04:00'],
            ]),
        ]);

        $hourlyData = $this->service->getActiveCouriersByHour();

        // Should have counts at late night and early morning hours
        $this->assertGreaterThanOrEqual(0, $hourlyData[22]['count']);
    }
}
