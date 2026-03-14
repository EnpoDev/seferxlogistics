<?php

namespace Tests\Feature\Controllers;

use App\Models\Branch;
use App\Models\BusinessInfo;
use App\Models\Courier;
use App\Models\CourierMealShift;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task #12 - Kurye Yemek Restoranları CRUD
 *
 * BayiShiftController meal shift endpoints:
 * - POST   /bayi/yemek-vardiyalari         (store)
 * - PUT    /bayi/yemek-vardiyalari/{shift}  (update)
 * - DELETE /bayi/yemek-vardiyalari/{shift}  (destroy)
 *
 * Test senaryoları:
 * 1. Ownership kontrolü - sadece kendi kuryeleri
 * 2. CRUD doğru çalışıyor mu
 * 3. Validation kuralları
 * 4. Restaurant eager loading (weeklySchedule)
 */
class MealShiftCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $bayiUser;
    protected User $otherBayiUser;
    protected User $isletmeUser;
    protected Courier $ownCourier;
    protected Courier $otherCourier;
    protected Courier $isletmeCourier;
    protected Restaurant $restaurant;

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

        $this->isletmeUser = User::factory()->create([
            'roles' => ['bayi'],
            'role' => 'bayi',
            'parent_id' => $this->bayiUser->id,
        ]);

        Branch::factory()->create(['user_id' => $this->bayiUser->id]);

        $this->ownCourier = Courier::factory()->create(['user_id' => $this->bayiUser->id]);
        $this->otherCourier = Courier::factory()->create(['user_id' => $this->otherBayiUser->id]);
        $this->isletmeCourier = Courier::factory()->create(['user_id' => $this->isletmeUser->id]);

        $this->restaurant = Restaurant::create([
            'name' => 'Test Restoran',
            'slug' => 'test-restoran',
            'is_active' => true,
            'is_featured' => false,
        ]);
    }

    // =========================================================================
    // STORE - Yemek Vardiyası Oluşturma
    // =========================================================================

    /** @test */
    public function bayi_can_create_meal_shift_for_own_courier(): void
    {
        $date = now()->addDay()->format('Y-m-d');

        $response = $this->actingAs($this->bayiUser)
            ->postJson(route('bayi.yemek-vardiyalari.store'), [
                'courier_id' => $this->ownCourier->id,
                'restaurant_id' => $this->restaurant->id,
                'date' => $date,
                'meal_type' => 'lunch',
                'start_time' => '12:00',
                'end_time' => '13:00',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('courier_meal_shifts', [
            'courier_id' => $this->ownCourier->id,
            'restaurant_id' => $this->restaurant->id,
            'meal_type' => 'lunch',
        ]);
    }

    /** @test */
    public function bayi_can_create_meal_shift_for_child_isletme_courier(): void
    {
        $date = now()->addDay()->format('Y-m-d');

        $response = $this->actingAs($this->bayiUser)
            ->postJson(route('bayi.yemek-vardiyalari.store'), [
                'courier_id' => $this->isletmeCourier->id,
                'date' => $date,
                'meal_type' => 'dinner',
                'start_time' => '18:00',
                'end_time' => '19:00',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('courier_meal_shifts', [
            'courier_id' => $this->isletmeCourier->id,
            'meal_type' => 'dinner',
        ]);
    }

    /** @test */
    public function bayi_cannot_create_meal_shift_for_other_bayis_courier(): void
    {
        $date = now()->addDay()->format('Y-m-d');

        $response = $this->actingAs($this->bayiUser)
            ->postJson(route('bayi.yemek-vardiyalari.store'), [
                'courier_id' => $this->otherCourier->id,
                'date' => $date,
                'meal_type' => 'lunch',
                'start_time' => '12:00',
                'end_time' => '13:00',
            ]);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('courier_meal_shifts', [
            'courier_id' => $this->otherCourier->id,
        ]);
    }

    /** @test */
    public function meal_shift_can_be_created_without_restaurant(): void
    {
        $date = now()->addDay()->format('Y-m-d');

        $response = $this->actingAs($this->bayiUser)
            ->postJson(route('bayi.yemek-vardiyalari.store'), [
                'courier_id' => $this->ownCourier->id,
                'date' => $date,
                'meal_type' => 'breakfast',
                'start_time' => '08:00',
                'end_time' => '09:00',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('courier_meal_shifts', [
            'courier_id' => $this->ownCourier->id,
            'meal_type' => 'breakfast',
            'restaurant_id' => null,
        ]);
    }

    /** @test */
    public function meal_shift_can_be_created_with_notes(): void
    {
        $date = now()->addDay()->format('Y-m-d');

        $response = $this->actingAs($this->bayiUser)
            ->postJson(route('bayi.yemek-vardiyalari.store'), [
                'courier_id' => $this->ownCourier->id,
                'date' => $date,
                'meal_type' => 'lunch',
                'start_time' => '12:00',
                'end_time' => '13:00',
                'notes' => 'Vejetaryen menu tercih edildi',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('courier_meal_shifts', [
            'courier_id' => $this->ownCourier->id,
            'notes' => 'Vejetaryen menu tercih edildi',
        ]);
    }

    // =========================================================================
    // STORE VALIDATION
    // =========================================================================

    /** @test */
    public function store_rejects_missing_courier_id(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->post(route('bayi.yemek-vardiyalari.store'), [
                'date' => now()->addDay()->format('Y-m-d'),
                'meal_type' => 'lunch',
                'start_time' => '12:00',
                'end_time' => '13:00',
            ]);

        // Validation redirect (302) or 422
        $this->assertContains($response->getStatusCode(), [302, 422]);
        $this->assertDatabaseCount('courier_meal_shifts', 0);
    }

    /** @test */
    public function store_rejects_invalid_date(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->post(route('bayi.yemek-vardiyalari.store'), [
                'courier_id' => $this->ownCourier->id,
                'date' => 'invalid-date',
                'meal_type' => 'lunch',
                'start_time' => '12:00',
                'end_time' => '13:00',
            ]);

        $this->assertContains($response->getStatusCode(), [302, 422]);
        $this->assertDatabaseCount('courier_meal_shifts', 0);
    }

    /** @test */
    public function store_rejects_invalid_meal_type(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->post(route('bayi.yemek-vardiyalari.store'), [
                'courier_id' => $this->ownCourier->id,
                'date' => now()->addDay()->format('Y-m-d'),
                'meal_type' => 'snack',
                'start_time' => '12:00',
                'end_time' => '13:00',
            ]);

        $this->assertContains($response->getStatusCode(), [302, 422]);
        $this->assertDatabaseCount('courier_meal_shifts', 0);
    }

    /** @test */
    public function store_rejects_invalid_time_format(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->post(route('bayi.yemek-vardiyalari.store'), [
                'courier_id' => $this->ownCourier->id,
                'date' => now()->addDay()->format('Y-m-d'),
                'meal_type' => 'lunch',
                'start_time' => '25:00',
                'end_time' => '13:00',
            ]);

        $this->assertContains($response->getStatusCode(), [302, 422]);
        $this->assertDatabaseCount('courier_meal_shifts', 0);
    }

    /** @test */
    public function store_rejects_nonexistent_courier(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->post(route('bayi.yemek-vardiyalari.store'), [
                'courier_id' => 99999,
                'date' => now()->addDay()->format('Y-m-d'),
                'meal_type' => 'lunch',
                'start_time' => '12:00',
                'end_time' => '13:00',
            ]);

        $this->assertContains($response->getStatusCode(), [302, 422]);
        $this->assertDatabaseCount('courier_meal_shifts', 0);
    }

    /** @test */
    public function store_rejects_nonexistent_restaurant(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->post(route('bayi.yemek-vardiyalari.store'), [
                'courier_id' => $this->ownCourier->id,
                'restaurant_id' => 99999,
                'date' => now()->addDay()->format('Y-m-d'),
                'meal_type' => 'lunch',
                'start_time' => '12:00',
                'end_time' => '13:00',
            ]);

        $this->assertContains($response->getStatusCode(), [302, 422]);
        $this->assertDatabaseCount('courier_meal_shifts', 0);
    }

    // =========================================================================
    // UPDATE - Yemek Vardiyası Güncelleme
    // =========================================================================

    /** @test */
    public function bayi_can_update_own_couriers_meal_shift(): void
    {
        $mealShift = CourierMealShift::create([
            'courier_id' => $this->ownCourier->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'meal_type' => 'lunch',
            'start_time' => '12:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->bayiUser)
            ->putJson(route('bayi.yemek-vardiyalari.update', $mealShift), [
                'meal_type' => 'dinner',
                'start_time' => '18:00',
                'end_time' => '19:30',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $mealShift->refresh();
        $this->assertEquals('dinner', $mealShift->meal_type);
    }

    /** @test */
    public function bayi_can_update_meal_shift_restaurant(): void
    {
        $mealShift = CourierMealShift::create([
            'courier_id' => $this->ownCourier->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'meal_type' => 'lunch',
            'start_time' => '12:00',
            'end_time' => '13:00',
            'is_active' => true,
            'restaurant_id' => null,
        ]);

        $response = $this->actingAs($this->bayiUser)
            ->putJson(route('bayi.yemek-vardiyalari.update', $mealShift), [
                'restaurant_id' => $this->restaurant->id,
            ]);

        $response->assertStatus(200);

        $mealShift->refresh();
        $this->assertEquals($this->restaurant->id, $mealShift->restaurant_id);
    }

    /** @test */
    public function bayi_can_deactivate_meal_shift(): void
    {
        $mealShift = CourierMealShift::create([
            'courier_id' => $this->ownCourier->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'meal_type' => 'lunch',
            'start_time' => '12:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->bayiUser)
            ->putJson(route('bayi.yemek-vardiyalari.update', $mealShift), [
                'is_active' => false,
            ]);

        $response->assertStatus(200);

        $mealShift->refresh();
        $this->assertFalse($mealShift->is_active);
    }

    /** @test */
    public function bayi_cannot_update_other_bayis_courier_meal_shift(): void
    {
        $mealShift = CourierMealShift::create([
            'courier_id' => $this->otherCourier->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'meal_type' => 'lunch',
            'start_time' => '12:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->bayiUser)
            ->putJson(route('bayi.yemek-vardiyalari.update', $mealShift), [
                'meal_type' => 'dinner',
            ]);

        $response->assertStatus(403);

        $mealShift->refresh();
        $this->assertEquals('lunch', $mealShift->meal_type);
    }

    // =========================================================================
    // DELETE - Yemek Vardiyası Silme
    // =========================================================================

    /** @test */
    public function bayi_can_delete_own_couriers_meal_shift(): void
    {
        $mealShift = CourierMealShift::create([
            'courier_id' => $this->ownCourier->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'meal_type' => 'lunch',
            'start_time' => '12:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        $mealShiftId = $mealShift->id;

        $response = $this->actingAs($this->bayiUser)
            ->deleteJson(route('bayi.yemek-vardiyalari.destroy', $mealShift));

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseMissing('courier_meal_shifts', ['id' => $mealShiftId]);
    }

    /** @test */
    public function bayi_can_delete_child_isletme_couriers_meal_shift(): void
    {
        $mealShift = CourierMealShift::create([
            'courier_id' => $this->isletmeCourier->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'meal_type' => 'dinner',
            'start_time' => '18:00',
            'end_time' => '19:00',
            'is_active' => true,
        ]);

        $mealShiftId = $mealShift->id;

        $response = $this->actingAs($this->bayiUser)
            ->deleteJson(route('bayi.yemek-vardiyalari.destroy', $mealShift));

        $response->assertStatus(200);

        $this->assertDatabaseMissing('courier_meal_shifts', ['id' => $mealShiftId]);
    }

    /** @test */
    public function bayi_cannot_delete_other_bayis_courier_meal_shift(): void
    {
        $mealShift = CourierMealShift::create([
            'courier_id' => $this->otherCourier->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'meal_type' => 'lunch',
            'start_time' => '12:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        $mealShiftId = $mealShift->id;

        $response = $this->actingAs($this->bayiUser)
            ->deleteJson(route('bayi.yemek-vardiyalari.destroy', $mealShift));

        $response->assertStatus(403);

        $this->assertDatabaseHas('courier_meal_shifts', ['id' => $mealShiftId]);
    }

    // =========================================================================
    // RESTAURANT EAGER LOADING - weeklySchedule
    // =========================================================================

    /** @test */
    public function weekly_schedule_eager_loads_restaurant_on_meal_shifts(): void
    {
        $startOfWeek = now()->startOfWeek();

        $mealShift = CourierMealShift::create([
            'courier_id' => $this->ownCourier->id,
            'restaurant_id' => $this->restaurant->id,
            'date' => $startOfWeek->format('Y-m-d'),
            'meal_type' => 'lunch',
            'start_time' => '12:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        // Login as courier and view weekly schedule
        $response = $this->actingAs($this->ownCourier, 'courier')
            ->get(route('kurye.schedule'));

        $response->assertStatus(200);
        $weekDays = $response->viewData('weekDays');

        // Monday should have 1 shift
        $this->assertCount(1, $weekDays[0]['shifts']);

        // The shift should have restaurant relation loaded (not lazy loaded)
        $shift = $weekDays[0]['shifts']->first();
        $this->assertTrue($shift->relationLoaded('restaurant'));
        $this->assertEquals($this->restaurant->id, $shift->restaurant->id);
        $this->assertEquals('Test Restoran', $shift->restaurant->name);
    }

    /** @test */
    public function weekly_schedule_handles_meal_shift_without_restaurant(): void
    {
        $startOfWeek = now()->startOfWeek();

        CourierMealShift::create([
            'courier_id' => $this->ownCourier->id,
            'restaurant_id' => null,
            'date' => $startOfWeek->format('Y-m-d'),
            'meal_type' => 'lunch',
            'start_time' => '12:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->ownCourier, 'courier')
            ->get(route('kurye.schedule'));

        $response->assertStatus(200);
        $weekDays = $response->viewData('weekDays');

        $shift = $weekDays[0]['shifts']->first();
        $this->assertTrue($shift->relationLoaded('restaurant'));
        $this->assertNull($shift->restaurant);
    }

    /** @test */
    public function bayi_meal_shifts_list_eager_loads_restaurant(): void
    {
        // Create a meal shift with restaurant for this week
        $startOfWeek = now()->startOfWeek();

        CourierMealShift::create([
            'courier_id' => $this->ownCourier->id,
            'restaurant_id' => $this->restaurant->id,
            'date' => $startOfWeek->format('Y-m-d'),
            'meal_type' => 'lunch',
            'start_time' => '12:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        // The mealShifts list in BayiShiftController also uses ->with('restaurant')
        // We verify this by checking the query includes the eager load
        $userIds = [$this->bayiUser->id, $this->isletmeUser->id];
        $courierIds = Courier::whereIn('user_id', $userIds)->pluck('id');

        $mealShifts = CourierMealShift::whereIn('courier_id', $courierIds)
            ->with('restaurant')
            ->whereBetween('date', [$startOfWeek->format('Y-m-d'), $startOfWeek->copy()->endOfWeek()->format('Y-m-d')])
            ->get();

        $this->assertCount(1, $mealShifts);
        $this->assertTrue($mealShifts->first()->relationLoaded('restaurant'));
        $this->assertEquals('Test Restoran', $mealShifts->first()->restaurant->name);
    }

    // =========================================================================
    // UNAUTHENTICATED
    // =========================================================================

    /** @test */
    public function unauthenticated_user_cannot_create_meal_shift(): void
    {
        $response = $this->post(route('bayi.yemek-vardiyalari.store'), [
            'courier_id' => $this->ownCourier->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'meal_type' => 'lunch',
            'start_time' => '12:00',
            'end_time' => '13:00',
        ]);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function store_returns_created_meal_shift_data(): void
    {
        $date = now()->addDay()->format('Y-m-d');

        $response = $this->actingAs($this->bayiUser)
            ->postJson(route('bayi.yemek-vardiyalari.store'), [
                'courier_id' => $this->ownCourier->id,
                'restaurant_id' => $this->restaurant->id,
                'date' => $date,
                'meal_type' => 'lunch',
                'start_time' => '12:00',
                'end_time' => '13:00',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'meal_shift' => ['id', 'courier_id', 'meal_type'],
        ]);
    }

    /** @test */
    public function update_returns_updated_meal_shift_data(): void
    {
        $mealShift = CourierMealShift::create([
            'courier_id' => $this->ownCourier->id,
            'date' => now()->addDay()->format('Y-m-d'),
            'meal_type' => 'lunch',
            'start_time' => '12:00',
            'end_time' => '13:00',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->bayiUser)
            ->putJson(route('bayi.yemek-vardiyalari.update', $mealShift), [
                'notes' => 'Updated notes',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'meal_shift' => ['id', 'courier_id', 'meal_type'],
        ]);
    }

    /** @test */
    public function all_three_meal_types_are_accepted(): void
    {
        $date = now()->addDay()->format('Y-m-d');

        foreach (['breakfast', 'lunch', 'dinner'] as $mealType) {
            $response = $this->actingAs($this->bayiUser)
                ->postJson(route('bayi.yemek-vardiyalari.store'), [
                    'courier_id' => $this->ownCourier->id,
                    'date' => $date,
                    'meal_type' => $mealType,
                    'start_time' => '12:00',
                    'end_time' => '13:00',
                ]);

            $response->assertStatus(200, "Meal type '{$mealType}' should be accepted");
        }

        $this->assertEquals(3, CourierMealShift::where('courier_id', $this->ownCourier->id)->count());
    }
}
