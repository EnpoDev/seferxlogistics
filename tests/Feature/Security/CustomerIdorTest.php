<?php

namespace Tests\Feature\Security;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * IDOR tests for CustomerController.
 * Ensures isletme users cannot access/delete customers or addresses
 * that belong to other branches.
 */
class CustomerIdorTest extends TestCase
{
    use RefreshDatabase;

    protected User $isletmeUser;
    protected User $otherIsletmeUser;
    protected User $bayiUser;
    protected User $adminUser;
    protected Branch $isletmeBranch;
    protected Branch $otherBranch;
    protected Customer $ownCustomer;
    protected Customer $otherCustomer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bayiUser = User::factory()->bayi()->create([
            'role' => 'bayi',
            'parent_id' => null,
        ]);

        $this->isletmeUser = User::factory()->create([
            'role' => 'isletme',
            'roles' => ['isletme'],
            'parent_id' => $this->bayiUser->id,
        ]);

        $this->otherIsletmeUser = User::factory()->create([
            'role' => 'isletme',
            'roles' => ['isletme'],
            'parent_id' => null,
        ]);

        $this->adminUser = User::factory()->admin()->create([
            'role' => 'admin',
        ]);

        $this->isletmeBranch = Branch::factory()->create(['user_id' => $this->isletmeUser->id]);
        $this->otherBranch = Branch::factory()->create(['user_id' => $this->otherIsletmeUser->id]);

        // Customer who ordered from isletme's branch
        $this->ownCustomer = Customer::factory()->create();
        Order::factory()->create([
            'branch_id' => $this->isletmeBranch->id,
            'customer_id' => $this->ownCustomer->id,
        ]);

        // Customer who ordered from other branch only
        $this->otherCustomer = Customer::factory()->create();
        Order::factory()->create([
            'branch_id' => $this->otherBranch->id,
            'customer_id' => $this->otherCustomer->id,
        ]);
    }

    // =========================================================================
    // Customer show IDOR
    // =========================================================================

    /** @test */
    public function isletme_cannot_view_other_branchs_customer(): void
    {
        // Set active branch for the isletme user
        session(['active_branch_id' => $this->isletmeBranch->id]);

        $response = $this->actingAs($this->isletmeUser)
            ->withSession(['active_branch_id' => $this->isletmeBranch->id])
            ->get(route('musteri.show', $this->otherCustomer));

        $response->assertStatus(403);
    }

    /** @test */
    public function isletme_can_view_own_branchs_customer(): void
    {
        $response = $this->actingAs($this->isletmeUser)
            ->withSession(['active_branch_id' => $this->isletmeBranch->id])
            ->get(route('musteri.show', $this->ownCustomer));

        $response->assertStatus(200);
    }

    // =========================================================================
    // Customer destroy IDOR
    // =========================================================================

    /** @test */
    public function isletme_cannot_delete_other_branchs_customer(): void
    {
        $response = $this->actingAs($this->isletmeUser)
            ->withSession(['active_branch_id' => $this->isletmeBranch->id])
            ->delete(route('musteri.destroy', $this->otherCustomer));

        $response->assertStatus(403);
        $this->assertDatabaseHas('customers', ['id' => $this->otherCustomer->id]);
    }

    /** @test */
    public function isletme_can_delete_own_branchs_customer(): void
    {
        $response = $this->actingAs($this->isletmeUser)
            ->withSession(['active_branch_id' => $this->isletmeBranch->id])
            ->delete(route('musteri.destroy', $this->ownCustomer));

        // Should succeed (redirect or 200)
        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    /** @test */
    public function admin_can_delete_any_customer(): void
    {
        $customer = Customer::factory()->create();
        Order::factory()->create([
            'branch_id' => $this->otherBranch->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('musteri.destroy', $customer));

        $this->assertNotEquals(403, $response->getStatusCode());
    }

    // =========================================================================
    // Customer Address delete IDOR
    // =========================================================================

    /** @test */
    public function isletme_cannot_delete_other_branchs_customer_address(): void
    {
        $address = CustomerAddress::create([
            'customer_id' => $this->otherCustomer->id,
            'title' => 'Ev',
            'address' => 'Baska Adres',
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->isletmeUser)
            ->withSession(['active_branch_id' => $this->isletmeBranch->id])
            ->deleteJson(route('musteri.address.destroy', $address));

        $response->assertStatus(403);
        $this->assertDatabaseHas('customer_addresses', ['id' => $address->id]);
    }

    /** @test */
    public function isletme_can_delete_own_branchs_customer_address(): void
    {
        $address = CustomerAddress::create([
            'customer_id' => $this->ownCustomer->id,
            'title' => 'Ev',
            'address' => 'Kendi Adres',
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->isletmeUser)
            ->withSession(['active_branch_id' => $this->isletmeBranch->id])
            ->deleteJson(route('musteri.address.destroy', $address));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('customer_addresses', ['id' => $address->id]);
    }

    /** @test */
    public function admin_can_delete_any_customer_address(): void
    {
        $address = CustomerAddress::create([
            'customer_id' => $this->otherCustomer->id,
            'title' => 'Is',
            'address' => 'Admin Siler',
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson(route('musteri.address.destroy', $address));

        $this->assertNotEquals(403, $response->getStatusCode());
    }

    // =========================================================================
    // Customer Address update IDOR
    // =========================================================================

    /** @test */
    public function isletme_cannot_update_other_branchs_customer_address(): void
    {
        $address = CustomerAddress::create([
            'customer_id' => $this->otherCustomer->id,
            'title' => 'Ev',
            'address' => 'Orijinal Adres',
            'is_default' => true,
        ]);

        $response = $this->actingAs($this->isletmeUser)
            ->withSession(['active_branch_id' => $this->isletmeBranch->id])
            ->putJson(route('musteri.address.update', $address), [
                'title' => 'Degistirilmis',
                'address' => 'Hack Adres',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('customer_addresses', [
            'id' => $address->id,
            'title' => 'Ev', // unchanged
        ]);
    }

    // =========================================================================
    // Unauthenticated access
    // =========================================================================

    /** @test */
    public function unauthenticated_user_cannot_access_customers(): void
    {
        $response = $this->get(route('musteri.show', $this->ownCustomer));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function unauthenticated_user_cannot_delete_customers(): void
    {
        $response = $this->delete(route('musteri.destroy', $this->ownCustomer));
        $response->assertRedirect(route('login'));
    }
}
