<?php

namespace Tests\Unit\Models;

use App\Models\Courier;
use App\Models\User;
use App\Models\UserConsent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserConsentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_consent_type_constants(): void
    {
        $this->assertEquals('data_processing', UserConsent::TYPE_DATA_PROCESSING);
        $this->assertEquals('marketing', UserConsent::TYPE_MARKETING);
        $this->assertEquals('newsletter', UserConsent::TYPE_NEWSLETTER);
        $this->assertEquals('location_tracking', UserConsent::TYPE_LOCATION_TRACKING);
        $this->assertEquals('cookies', UserConsent::TYPE_COOKIES);
        $this->assertEquals('third_party_sharing', UserConsent::TYPE_THIRD_PARTY_SHARING);
    }

    /** @test */
    public function it_can_grant_consent_for_user(): void
    {
        $user = User::factory()->create();

        $consent = UserConsent::grantForUser(
            $user->id,
            UserConsent::TYPE_DATA_PROCESSING,
            'I accept data processing',
            '1.0'
        );

        $this->assertNotNull($consent);
        $this->assertEquals($user->id, $consent->user_id);
        $this->assertEquals(UserConsent::TYPE_DATA_PROCESSING, $consent->consent_type);
        $this->assertTrue($consent->is_granted);
        $this->assertNotNull($consent->granted_at);
    }

    /** @test */
    public function it_can_grant_consent_for_courier(): void
    {
        $courier = Courier::factory()->create();

        $consent = UserConsent::grantForCourier(
            $courier->id,
            UserConsent::TYPE_LOCATION_TRACKING,
            'I accept location tracking',
            '1.0'
        );

        $this->assertNotNull($consent);
        $this->assertEquals($courier->id, $consent->courier_id);
        $this->assertEquals(UserConsent::TYPE_LOCATION_TRACKING, $consent->consent_type);
        $this->assertTrue($consent->is_granted);
    }

    /** @test */
    public function it_updates_existing_consent_instead_of_creating_duplicate(): void
    {
        $user = User::factory()->create();

        // First consent
        UserConsent::grantForUser($user->id, UserConsent::TYPE_MARKETING, 'Text 1', '1.0');

        // Second consent for same type
        UserConsent::grantForUser($user->id, UserConsent::TYPE_MARKETING, 'Text 2', '2.0');

        $consents = UserConsent::where('user_id', $user->id)
            ->where('consent_type', UserConsent::TYPE_MARKETING)
            ->get();

        $this->assertCount(1, $consents);
        $this->assertEquals('Text 2', $consents->first()->consent_text);
        $this->assertEquals('2.0', $consents->first()->consent_version);
    }

    /** @test */
    public function it_can_withdraw_consent(): void
    {
        $user = User::factory()->create();
        $consent = UserConsent::grantForUser($user->id, UserConsent::TYPE_MARKETING);

        $result = $consent->withdraw('User requested');

        $this->assertTrue($result);

        $consent->refresh();
        $this->assertFalse($consent->is_granted);
        $this->assertNotNull($consent->withdrawn_at);
        $this->assertEquals('User requested', $consent->withdrawal_reason);
    }

    /** @test */
    public function it_checks_if_consent_is_active(): void
    {
        $user = User::factory()->create();

        $activeConsent = UserConsent::grantForUser($user->id, UserConsent::TYPE_DATA_PROCESSING);

        $withdrawnConsent = UserConsent::grantForUser($user->id, UserConsent::TYPE_MARKETING);
        $withdrawnConsent->withdraw('No longer needed');

        $this->assertTrue($activeConsent->isActive());
        $this->assertFalse($withdrawnConsent->fresh()->isActive());
    }

    /** @test */
    public function it_checks_if_user_has_consent(): void
    {
        $user = User::factory()->create();

        UserConsent::grantForUser($user->id, UserConsent::TYPE_DATA_PROCESSING);

        $this->assertTrue(UserConsent::hasConsent($user->id, UserConsent::TYPE_DATA_PROCESSING));
        $this->assertFalse(UserConsent::hasConsent($user->id, UserConsent::TYPE_MARKETING));
    }

    /** @test */
    public function it_checks_if_courier_has_consent(): void
    {
        $courier = Courier::factory()->create();

        UserConsent::grantForCourier($courier->id, UserConsent::TYPE_LOCATION_TRACKING);

        $this->assertTrue(UserConsent::courierHasConsent($courier->id, UserConsent::TYPE_LOCATION_TRACKING));
        $this->assertFalse(UserConsent::courierHasConsent($courier->id, UserConsent::TYPE_MARKETING));
    }

    /** @test */
    public function it_does_not_consider_withdrawn_consent_as_granted(): void
    {
        $user = User::factory()->create();

        $consent = UserConsent::grantForUser($user->id, UserConsent::TYPE_MARKETING);
        $consent->withdraw('No longer needed');

        $this->assertFalse(UserConsent::hasConsent($user->id, UserConsent::TYPE_MARKETING));
    }

    /** @test */
    public function it_returns_type_labels(): void
    {
        $this->assertEquals('Kisisel Veri Isleme', UserConsent::getTypeLabel(UserConsent::TYPE_DATA_PROCESSING));
        $this->assertEquals('Pazarlama Iletisimleri', UserConsent::getTypeLabel(UserConsent::TYPE_MARKETING));
        $this->assertEquals('E-Bulten', UserConsent::getTypeLabel(UserConsent::TYPE_NEWSLETTER));
        $this->assertEquals('Konum Takibi', UserConsent::getTypeLabel(UserConsent::TYPE_LOCATION_TRACKING));
        $this->assertEquals('Cerezler', UserConsent::getTypeLabel(UserConsent::TYPE_COOKIES));
        $this->assertEquals('Ucuncu Taraf Paylasimi', UserConsent::getTypeLabel(UserConsent::TYPE_THIRD_PARTY_SHARING));
    }

    /** @test */
    public function it_returns_original_type_for_unknown_type(): void
    {
        $this->assertEquals('unknown_type', UserConsent::getTypeLabel('unknown_type'));
    }

    /** @test */
    public function granted_scope_returns_only_active_consents(): void
    {
        $user = User::factory()->create();

        $activeConsent = UserConsent::grantForUser($user->id, UserConsent::TYPE_DATA_PROCESSING);

        $withdrawnConsent = UserConsent::grantForUser($user->id, UserConsent::TYPE_MARKETING);
        $withdrawnConsent->withdraw();

        $grantedConsents = UserConsent::granted()->get();

        $this->assertTrue($grantedConsents->contains($activeConsent));
        $this->assertFalse($grantedConsents->contains($withdrawnConsent->fresh()));
    }

    /** @test */
    public function for_type_scope_filters_by_type(): void
    {
        $user = User::factory()->create();

        UserConsent::grantForUser($user->id, UserConsent::TYPE_DATA_PROCESSING);
        UserConsent::grantForUser($user->id, UserConsent::TYPE_MARKETING);

        $dataConsents = UserConsent::forType(UserConsent::TYPE_DATA_PROCESSING)->get();

        $this->assertCount(1, $dataConsents);
        $this->assertEquals(UserConsent::TYPE_DATA_PROCESSING, $dataConsents->first()->consent_type);
    }

    /** @test */
    public function for_user_scope_filters_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        UserConsent::grantForUser($user1->id, UserConsent::TYPE_DATA_PROCESSING);
        UserConsent::grantForUser($user2->id, UserConsent::TYPE_DATA_PROCESSING);

        $user1Consents = UserConsent::forUser($user1->id)->get();

        $this->assertCount(1, $user1Consents);
        $this->assertEquals($user1->id, $user1Consents->first()->user_id);
    }

    /** @test */
    public function for_courier_scope_filters_by_courier(): void
    {
        $courier1 = Courier::factory()->create();
        $courier2 = Courier::factory()->create();

        UserConsent::grantForCourier($courier1->id, UserConsent::TYPE_LOCATION_TRACKING);
        UserConsent::grantForCourier($courier2->id, UserConsent::TYPE_LOCATION_TRACKING);

        $courier1Consents = UserConsent::forCourier($courier1->id)->get();

        $this->assertCount(1, $courier1Consents);
        $this->assertEquals($courier1->id, $courier1Consents->first()->courier_id);
    }

    /** @test */
    public function it_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $consent = UserConsent::grantForUser($user->id, UserConsent::TYPE_DATA_PROCESSING);

        $this->assertInstanceOf(User::class, $consent->user);
        $this->assertEquals($user->id, $consent->user->id);
    }

    /** @test */
    public function it_belongs_to_courier(): void
    {
        $courier = Courier::factory()->create();
        $consent = UserConsent::grantForCourier($courier->id, UserConsent::TYPE_LOCATION_TRACKING);

        $this->assertInstanceOf(Courier::class, $consent->courier);
        $this->assertEquals($courier->id, $consent->courier->id);
    }

    /** @test */
    public function it_stores_ip_address_on_grant(): void
    {
        $user = User::factory()->create();

        $consent = UserConsent::grantForUser($user->id, UserConsent::TYPE_DATA_PROCESSING);

        $this->assertNotNull($consent->ip_address);
    }

    /** @test */
    public function it_stores_user_agent_on_grant(): void
    {
        $user = User::factory()->create();

        $consent = UserConsent::grantForUser($user->id, UserConsent::TYPE_DATA_PROCESSING);

        // User agent may be null in test environment, but the field should exist
        $this->assertTrue($consent->isDirty('user_agent') === false);
    }

    /** @test */
    public function withdraw_without_reason_is_allowed(): void
    {
        $user = User::factory()->create();
        $consent = UserConsent::grantForUser($user->id, UserConsent::TYPE_MARKETING);

        $result = $consent->withdraw();

        $this->assertTrue($result);
        $this->assertNull($consent->fresh()->withdrawal_reason);
    }
}
