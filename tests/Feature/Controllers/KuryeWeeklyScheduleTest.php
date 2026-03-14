<?php

namespace Tests\Feature\Controllers;

use App\Models\Branch;
use App\Models\Courier;
use App\Models\CourierMealBenefit;
use App\Models\CourierMealShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task #22 - Kurye Haftalık Takvim
 *
 * KuryeAppController::weeklySchedule() metodu
 * Route: GET /kurye/takvim
 *
 * Test senaryoları:
 * - Haftalık veri doğru çekiliyor mu
 * - Hafta navigasyonu çalışıyor mu (week offset)
 * - Başka kuryelerin verisi gelmiyor mu (veri izolasyonu)
 * - Yetkilendirme kontrolleri
 */
class KuryeWeeklyScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected Courier $courier;
    protected Courier $otherCourier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->courier = Courier::factory()->create([
            'password' => bcrypt('password'),
            'is_app_enabled' => true,
        ]);

        $this->otherCourier = Courier::factory()->create([
            'password' => bcrypt('password'),
            'is_app_enabled' => true,
        ]);
    }

    // =========================================================================
    // TEMEL ERİŞİM TESTLERİ
    // =========================================================================

    /** @test */
    public function authenticated_courier_can_view_weekly_schedule(): void
    {
        $response = $this->actingAs($this->courier, 'courier')
            ->get(route('kurye.schedule'));

        $response->assertStatus(200);
        $response->assertViewIs('kurye.schedule');
        $response->assertViewHas('courier');
        $response->assertViewHas('weekDays');
        $response->assertViewHas('startOfWeek');
        $response->assertViewHas('endOfWeek');
        $response->assertViewHas('weekOffset');
    }

    /** @test */
    public function unauthenticated_user_cannot_view_weekly_schedule(): void
    {
        $response = $this->get(route('kurye.schedule'));

        $response->assertRedirect(route('kurye.login'));
    }

    // =========================================================================
    // HAFTALIK VERİ DOĞRULUĞU
    // =========================================================================

    /** @test */
    public function schedule_returns_7_days_for_current_week(): void
    {
        $response = $this->actingAs($this->courier, 'courier')
            ->get(route('kurye.schedule'));

        $response->assertStatus(200);
        $weekDays = $response->viewData('weekDays');

        $this->assertCount(7, $weekDays);
    }

    /** @test */
    public function schedule_shows_meal_shifts_for_current_week(): void
    {
        $startOfWeek = now()->startOfWeek();

        // Create meal shifts for this week
        $shift1 = CourierMealShift::create([
            'courier_id' => $this->courier->id,
            'date' => $startOfWeek->format('Y-m-d'),
            'meal_type' => 'lunch',
            'start_time' => '12:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        $shift2 = CourierMealShift::create([
            'courier_id' => $this->courier->id,
            'date' => $startOfWeek->copy()->addDays(2)->format('Y-m-d'),
            'meal_type' => 'dinner',
            'start_time' => '18:00',
            'end_time' => '19:00',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->courier, 'courier')
            ->get(route('kurye.schedule'));

        $response->assertStatus(200);
        $weekDays = $response->viewData('weekDays');

        // Day 0 (Monday) should have 1 shift
        $this->assertCount(1, $weekDays[0]['shifts']);

        // Day 2 (Wednesday) should have 1 shift
        $this->assertCount(1, $weekDays[2]['shifts']);

        // Other days should have 0 shifts
        $this->assertCount(0, $weekDays[1]['shifts']);
        $this->assertCount(0, $weekDays[3]['shifts']);
    }

    /** @test */
    public function schedule_shows_meal_benefits_for_current_week(): void
    {
        $startOfWeek = now()->startOfWeek();
        $branch = Branch::factory()->create();

        $benefit = CourierMealBenefit::create([
            'courier_id' => $this->courier->id,
            'branch_id' => $branch->id,
            'benefit_date' => $startOfWeek->copy()->addDays(1)->format('Y-m-d'),
            'meal_type' => 'lunch',
            'meal_value' => 50.00,
            'is_used' => false,
        ]);

        $response = $this->actingAs($this->courier, 'courier')
            ->get(route('kurye.schedule'));

        $response->assertStatus(200);
        $weekDays = $response->viewData('weekDays');

        // Day 1 (Tuesday) should have 1 benefit
        $this->assertCount(1, $weekDays[1]['benefits']);

        // Other days should have 0 benefits
        $this->assertCount(0, $weekDays[0]['benefits']);
    }

    /** @test */
    public function inactive_meal_shifts_are_not_shown(): void
    {
        $startOfWeek = now()->startOfWeek();

        // Active shift
        CourierMealShift::create([
            'courier_id' => $this->courier->id,
            'date' => $startOfWeek->format('Y-m-d'),
            'meal_type' => 'lunch',
            'start_time' => '12:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        // Inactive shift (should not appear)
        CourierMealShift::create([
            'courier_id' => $this->courier->id,
            'date' => $startOfWeek->format('Y-m-d'),
            'meal_type' => 'dinner',
            'start_time' => '18:00',
            'end_time' => '19:00',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->courier, 'courier')
            ->get(route('kurye.schedule'));

        $response->assertStatus(200);
        $weekDays = $response->viewData('weekDays');

        // Only 1 active shift should appear
        $this->assertCount(1, $weekDays[0]['shifts']);
    }

    // =========================================================================
    // HAFTA NAVİGASYONU
    // =========================================================================

    /** @test */
    public function schedule_defaults_to_current_week(): void
    {
        $response = $this->actingAs($this->courier, 'courier')
            ->get(route('kurye.schedule'));

        $response->assertStatus(200);
        $weekOffset = $response->viewData('weekOffset');
        $startOfWeek = $response->viewData('startOfWeek');

        $this->assertEquals(0, $weekOffset);
        $this->assertTrue($startOfWeek->isSameDay(now()->startOfWeek()));
    }

    /** @test */
    public function schedule_can_navigate_to_next_week(): void
    {
        $response = $this->actingAs($this->courier, 'courier')
            ->get(route('kurye.schedule', ['week' => 1]));

        $response->assertStatus(200);
        $weekOffset = $response->viewData('weekOffset');
        $startOfWeek = $response->viewData('startOfWeek');

        $this->assertEquals(1, $weekOffset);

        $expectedStart = now()->startOfWeek()->addWeek();
        $this->assertTrue($startOfWeek->isSameDay($expectedStart));
    }

    /** @test */
    public function schedule_can_navigate_to_previous_week(): void
    {
        $response = $this->actingAs($this->courier, 'courier')
            ->get(route('kurye.schedule', ['week' => -1]));

        $response->assertStatus(200);
        $weekOffset = $response->viewData('weekOffset');
        $startOfWeek = $response->viewData('startOfWeek');

        $this->assertEquals(-1, $weekOffset);

        $expectedStart = now()->startOfWeek()->subWeek();
        $this->assertTrue($startOfWeek->isSameDay($expectedStart));
    }

    /** @test */
    public function schedule_shows_correct_data_for_next_week(): void
    {
        $nextWeekStart = now()->startOfWeek()->addWeek();

        // Create shift for next week
        CourierMealShift::create([
            'courier_id' => $this->courier->id,
            'date' => $nextWeekStart->format('Y-m-d'),
            'meal_type' => 'lunch',
            'start_time' => '12:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        // Create shift for current week (should NOT appear in next week view)
        CourierMealShift::create([
            'courier_id' => $this->courier->id,
            'date' => now()->startOfWeek()->format('Y-m-d'),
            'meal_type' => 'dinner',
            'start_time' => '18:00',
            'end_time' => '19:00',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->courier, 'courier')
            ->get(route('kurye.schedule', ['week' => 1]));

        $response->assertStatus(200);
        $weekDays = $response->viewData('weekDays');

        // Next week's Monday should have 1 shift
        $this->assertCount(1, $weekDays[0]['shifts']);

        // No current week data should leak into next week view
        $totalShifts = collect($weekDays)->sum(fn($day) => $day['shifts']->count());
        $this->assertEquals(1, $totalShifts);
    }

    // =========================================================================
    // VERİ İZOLASYONU - Başka kuryelerin verisi gelmemeli
    // =========================================================================

    /** @test */
    public function schedule_does_not_show_other_couriers_meal_shifts(): void
    {
        $startOfWeek = now()->startOfWeek();

        // Own courier's shift
        CourierMealShift::create([
            'courier_id' => $this->courier->id,
            'date' => $startOfWeek->format('Y-m-d'),
            'meal_type' => 'lunch',
            'start_time' => '12:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        // Other courier's shift (should NOT appear)
        CourierMealShift::create([
            'courier_id' => $this->otherCourier->id,
            'date' => $startOfWeek->format('Y-m-d'),
            'meal_type' => 'dinner',
            'start_time' => '18:00',
            'end_time' => '19:00',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->courier, 'courier')
            ->get(route('kurye.schedule'));

        $response->assertStatus(200);
        $weekDays = $response->viewData('weekDays');

        // Only own courier's shift should appear (1 shift, not 2)
        $totalShifts = collect($weekDays)->sum(fn($day) => $day['shifts']->count());
        $this->assertEquals(1, $totalShifts);
    }

    /** @test */
    public function schedule_does_not_show_other_couriers_meal_benefits(): void
    {
        $startOfWeek = now()->startOfWeek();
        $branch = Branch::factory()->create();

        // Own courier's benefit
        CourierMealBenefit::create([
            'courier_id' => $this->courier->id,
            'branch_id' => $branch->id,
            'benefit_date' => $startOfWeek->format('Y-m-d'),
            'meal_type' => 'lunch',
            'meal_value' => 50.00,
            'is_used' => false,
        ]);

        // Other courier's benefit (should NOT appear)
        CourierMealBenefit::create([
            'courier_id' => $this->otherCourier->id,
            'branch_id' => $branch->id,
            'benefit_date' => $startOfWeek->format('Y-m-d'),
            'meal_type' => 'lunch',
            'meal_value' => 50.00,
            'is_used' => false,
        ]);

        $response = $this->actingAs($this->courier, 'courier')
            ->get(route('kurye.schedule'));

        $response->assertStatus(200);
        $weekDays = $response->viewData('weekDays');

        // Only own courier's benefit should appear (1, not 2)
        $totalBenefits = collect($weekDays)->sum(fn($day) => $day['benefits']->count());
        $this->assertEquals(1, $totalBenefits);
    }

    /** @test */
    public function schedule_returns_correct_courier_in_view(): void
    {
        $response = $this->actingAs($this->courier, 'courier')
            ->get(route('kurye.schedule'));

        $response->assertStatus(200);
        $viewCourier = $response->viewData('courier');

        $this->assertEquals($this->courier->id, $viewCourier->id);
        $this->assertNotEquals($this->otherCourier->id, $viewCourier->id);
    }

    // =========================================================================
    // EDGE CASES
    // =========================================================================

    /** @test */
    public function schedule_handles_empty_week_gracefully(): void
    {
        // No shifts or benefits exist
        $response = $this->actingAs($this->courier, 'courier')
            ->get(route('kurye.schedule'));

        $response->assertStatus(200);
        $weekDays = $response->viewData('weekDays');

        $this->assertCount(7, $weekDays);

        foreach ($weekDays as $day) {
            $this->assertCount(0, $day['shifts']);
            $this->assertCount(0, $day['benefits']);
        }
    }

    /** @test */
    public function schedule_date_range_is_correct_for_start_and_end_of_week(): void
    {
        $response = $this->actingAs($this->courier, 'courier')
            ->get(route('kurye.schedule'));

        $response->assertStatus(200);
        $startOfWeek = $response->viewData('startOfWeek');
        $endOfWeek = $response->viewData('endOfWeek');

        // Start of week should be Monday
        $this->assertTrue($startOfWeek->isMonday());

        // End of week should be Sunday
        $this->assertTrue($endOfWeek->isSunday());

        // Difference should be ~6-7 days (endOfWeek includes end of day)
        $diff = $startOfWeek->diffInDays($endOfWeek);
        $this->assertGreaterThanOrEqual(6, $diff);
        $this->assertLessThanOrEqual(7, $diff);
    }

    /** @test */
    public function schedule_week_days_have_correct_dates(): void
    {
        $response = $this->actingAs($this->courier, 'courier')
            ->get(route('kurye.schedule'));

        $response->assertStatus(200);
        $weekDays = $response->viewData('weekDays');
        $startOfWeek = $response->viewData('startOfWeek');

        for ($i = 0; $i < 7; $i++) {
            $expectedDate = $startOfWeek->copy()->addDays($i);
            $this->assertTrue(
                $weekDays[$i]['date']->isSameDay($expectedDate),
                "Day {$i} should be {$expectedDate->format('Y-m-d')} but got {$weekDays[$i]['date']->format('Y-m-d')}"
            );
        }
    }
}
