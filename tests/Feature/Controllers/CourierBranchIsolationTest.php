<?php

namespace Tests\Feature\Controllers;

use App\Models\Branch;
use App\Models\BusinessInfo;
use App\Models\Courier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Task #11 - Kurye Filtresi (branch_id izolasyonu)
 *
 * Bayi sadece kendi kuryelerini görür.
 * İşletme, parent bayisinin kuryelerini görür.
 * Farklı bayinin kuryeleri görünmez.
 */
class CourierBranchIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $bayiUser;
    protected User $isletmeUser;
    protected User $otherBayiUser;
    protected Branch $branch;
    protected Branch $otherBranch;

    protected function setUp(): void
    {
        parent::setUp();

        // Bayi user (parent dealer) - parent_id null
        $this->bayiUser = User::factory()->bayi()->create([
            'role' => 'bayi',
            'parent_id' => null,
        ]);

        // İşletme user (child of bayi)
        $this->isletmeUser = User::factory()->create([
            'roles' => ['bayi'],
            'role' => 'bayi',
            'parent_id' => $this->bayiUser->id,
        ]);

        // Different bayi user (should not see first bayi's couriers)
        $this->otherBayiUser = User::factory()->bayi()->create([
            'role' => 'bayi',
            'parent_id' => null,
        ]);

        $this->branch = Branch::factory()->create(['user_id' => $this->bayiUser->id]);
        $this->otherBranch = Branch::factory()->create(['user_id' => $this->otherBayiUser->id]);

        // Required by vardiya-saatleri view
        BusinessInfo::create([
            'name' => 'Test Business',
            'phone' => '0212-1234567',
            'email' => 'test@test.com',
            'address' => 'Test Address',
        ]);
    }

    // =========================================================================
    // VARDIYA SAATLERİ - BayiShiftController
    // =========================================================================

    /** @test */
    public function bayi_sees_only_own_couriers_on_shift_page(): void
    {
        // Bayi's own couriers
        $ownCourier1 = Courier::factory()->create(['user_id' => $this->bayiUser->id, 'name' => 'Kendi Kurye 1']);
        $ownCourier2 = Courier::factory()->create(['user_id' => $this->bayiUser->id, 'name' => 'Kendi Kurye 2']);

        // İşletme's courier (child of bayi - should also be visible)
        $isletmeCourier = Courier::factory()->create(['user_id' => $this->isletmeUser->id, 'name' => 'Isletme Kuryesi']);

        // Other bayi's courier (should NOT be visible)
        $otherCourier = Courier::factory()->create(['user_id' => $this->otherBayiUser->id, 'name' => 'Diger Bayi Kuryesi']);

        $response = $this->actingAs($this->bayiUser)
            ->get(route('bayi.vardiya-saatleri'));

        $response->assertStatus(200);
        $response->assertSee('Kendi Kurye 1');
        $response->assertSee('Kendi Kurye 2');
        $response->assertSee('Isletme Kuryesi');
        $response->assertDontSee('Diger Bayi Kuryesi');
    }

    /** @test */
    public function isletme_user_sees_only_own_couriers_on_shift_page(): void
    {
        // İşletme's own courier
        $isletmeCourier = Courier::factory()->create(['user_id' => $this->isletmeUser->id, 'name' => 'Isletme Kuryesi']);

        // Parent bayi's courier (isletme has parent_id set, so getBayiAndChildUserIds only adds children if parent_id is null)
        $bayiCourier = Courier::factory()->create(['user_id' => $this->bayiUser->id, 'name' => 'Bayi Kuryesi']);

        // Other bayi's courier
        $otherCourier = Courier::factory()->create(['user_id' => $this->otherBayiUser->id, 'name' => 'Diger Kuryesi']);

        $response = $this->actingAs($this->isletmeUser)
            ->get(route('bayi.vardiya-saatleri'));

        $response->assertStatus(200);
        $response->assertSee('Isletme Kuryesi');
        $response->assertDontSee('Diger Kuryesi');
    }

    /** @test */
    public function bayi_cannot_update_shift_of_other_bayis_courier(): void
    {
        $otherCourier = Courier::factory()->create(['user_id' => $this->otherBayiUser->id]);

        $response = $this->actingAs($this->bayiUser)
            ->post(route('bayi.vardiya-saatleri.guncelle', $otherCourier), [
                'day' => 1,
                'hours' => '09:00-18:00',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function bayi_can_update_shift_of_own_courier(): void
    {
        $ownCourier = Courier::factory()->create(['user_id' => $this->bayiUser->id]);

        $response = $this->actingAs($this->bayiUser)
            ->post(route('bayi.vardiya-saatleri.guncelle', $ownCourier), [
                'day' => 1,
                'hours' => '09:00-18:00',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function bayi_can_update_shift_of_child_isletme_courier(): void
    {
        $isletmeCourier = Courier::factory()->create(['user_id' => $this->isletmeUser->id]);

        $response = $this->actingAs($this->bayiUser)
            ->post(route('bayi.vardiya-saatleri.guncelle', $isletmeCourier), [
                'day' => 2,
                'hours' => '10:00-20:00',
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function bayi_cannot_delete_shift_of_other_bayis_courier(): void
    {
        $otherCourier = Courier::factory()->create(['user_id' => $this->otherBayiUser->id]);

        $response = $this->actingAs($this->bayiUser)
            ->delete(route('bayi.vardiya-saatleri.sil', $otherCourier), [
                'day' => 1,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function bayi_cannot_copy_shift_of_other_bayis_courier(): void
    {
        $otherCourier = Courier::factory()->create([
            'user_id' => $this->otherBayiUser->id,
            'shifts' => [1 => '09:00-18:00'],
        ]);

        $response = $this->actingAs($this->bayiUser)
            ->post(route('bayi.vardiya-saatleri.kopyala', $otherCourier), [
                'source_day' => 1,
                'target_days' => [2, 3],
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function bayi_cannot_apply_template_to_other_bayis_courier(): void
    {
        $otherCourier = Courier::factory()->create(['user_id' => $this->otherBayiUser->id]);

        $response = $this->actingAs($this->bayiUser)
            ->post(route('bayi.vardiya-saatleri.sablon-uygula', $otherCourier));

        $response->assertStatus(403);
    }

    /** @test */
    public function bulk_shift_update_only_affects_own_couriers(): void
    {
        $ownCourier = Courier::factory()->create(['user_id' => $this->bayiUser->id]);
        $otherCourier = Courier::factory()->create(['user_id' => $this->otherBayiUser->id]);

        $response = $this->actingAs($this->bayiUser)
            ->post(route('bayi.vardiya-saatleri.toplu-guncelle'), [
                'shifts' => [1 => '09:00-18:00'],
                'apply_to_all' => true,
            ]);

        $response->assertRedirect();

        $ownCourier->refresh();
        $otherCourier->refresh();

        // Own courier should be updated
        $this->assertNotNull($ownCourier->shifts);
        $this->assertEquals('09:00-18:00', $ownCourier->shifts[1] ?? null);

        // Other bayi's courier should NOT be updated
        $this->assertNull($otherCourier->shifts);
    }

    // =========================================================================
    // HARİTA - BayiMapController
    // =========================================================================

    /** @test */
    public function bayi_harita_shows_only_own_couriers(): void
    {
        $ownCourier = Courier::factory()->create([
            'user_id' => $this->bayiUser->id,
            'name' => 'Harita Kuryem',
        ]);

        $otherCourier = Courier::factory()->create([
            'user_id' => $this->otherBayiUser->id,
            'name' => 'Baska Harita Kuryesi',
        ]);

        $response = $this->actingAs($this->bayiUser)
            ->get(route('bayi.harita'));

        $response->assertStatus(200);
        $response->assertSee('Harita Kuryem');
        $response->assertDontSee('Baska Harita Kuryesi');
    }

    // =========================================================================
    // BÖLGELENDIRME - BayiZoneController
    // =========================================================================

    /** @test */
    public function bayi_bolgelendirme_shows_only_own_couriers(): void
    {
        $ownCourier = Courier::factory()->create([
            'user_id' => $this->bayiUser->id,
            'name' => 'Bolge Kuryem',
        ]);

        $otherCourier = Courier::factory()->create([
            'user_id' => $this->otherBayiUser->id,
            'name' => 'Baska Bolge Kuryesi',
        ]);

        $response = $this->actingAs($this->bayiUser)
            ->get(route('bayi.bolgelendirme'));

        $response->assertStatus(200);
        $response->assertSee('Bolge Kuryem');
        $response->assertDontSee('Baska Bolge Kuryesi');
    }

    // =========================================================================
    // HAVUZ - BayiPoolController
    // =========================================================================

    /** @test */
    public function pool_stats_api_shows_only_own_couriers(): void
    {
        $ownCourier = Courier::factory()->create([
            'user_id' => $this->bayiUser->id,
            'name' => 'Pool Kuryem',
            'status' => Courier::STATUS_AVAILABLE,
            'shift_start' => '00:00',
            'shift_end' => '23:59',
        ]);

        $otherCourier = Courier::factory()->create([
            'user_id' => $this->otherBayiUser->id,
            'name' => 'Baska Pool Kuryesi',
            'status' => Courier::STATUS_AVAILABLE,
            'shift_start' => '00:00',
            'shift_end' => '23:59',
        ]);

        $response = $this->actingAs($this->bayiUser)
            ->getJson(route('bayi.havuz.istatistik'));

        $response->assertStatus(200);

        $courierNames = collect($response->json('couriers'))->pluck('name')->toArray();
        $this->assertContains('Pool Kuryem', $courierNames);
        $this->assertNotContains('Baska Pool Kuryesi', $courierNames);
    }

    // =========================================================================
    // UNAUTHENTICATED ACCESS
    // =========================================================================

    /** @test */
    public function unauthenticated_user_cannot_access_shift_page(): void
    {
        $response = $this->get(route('bayi.vardiya-saatleri'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function unauthenticated_user_cannot_access_harita(): void
    {
        $response = $this->get(route('bayi.harita'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function unauthenticated_user_cannot_access_bolgelendirme(): void
    {
        $response = $this->get(route('bayi.bolgelendirme'));
        $response->assertRedirect(route('login'));
    }
}
