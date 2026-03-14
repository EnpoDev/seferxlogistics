<?php

namespace Tests\Feature\Security;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * IDOR tests for OrderController.
 * Ensures bayi users cannot access/modify orders belonging to other bayis' branches,
 * while admins can access everything.
 */
class OrderIdorTest extends TestCase
{
    use RefreshDatabase;

    protected User $bayiUser;
    protected User $otherBayiUser;
    protected User $adminUser;
    protected Branch $ownBranch;
    protected Branch $otherBranch;
    protected Order $ownOrder;
    protected Order $otherOrder;

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
        $this->adminUser = User::factory()->admin()->create([
            'role' => 'admin',
        ]);

        $this->ownBranch = Branch::factory()->create(['user_id' => $this->bayiUser->id]);
        $this->otherBranch = Branch::factory()->create(['user_id' => $this->otherBayiUser->id]);

        $this->ownOrder = Order::factory()->create([
            'branch_id' => $this->ownBranch->id,
            'status' => 'pending',
        ]);
        $this->otherOrder = Order::factory()->create([
            'branch_id' => $this->otherBranch->id,
            'status' => 'pending',
        ]);
    }

    // =========================================================================
    // Edit (GET) IDOR
    // =========================================================================

    /** @test */
    public function bayi_cannot_edit_other_branchs_order(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->get(route('siparis.edit', $this->otherOrder));

        $response->assertStatus(403);
    }

    /** @test */
    public function bayi_can_edit_own_branchs_order(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->get(route('siparis.edit', $this->ownOrder));

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_edit_any_order(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('siparis.edit', $this->otherOrder));

        $response->assertStatus(200);
    }

    // =========================================================================
    // Update (PUT) IDOR
    // =========================================================================

    /** @test */
    public function bayi_cannot_update_other_branchs_order(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($this->bayiUser)
            ->put(route('siparis.update', $this->otherOrder), [
                'customer_name' => 'Degistirilmis',
                'customer_phone' => '05321234567',
                'customer_address' => 'Yeni Adres',
                'delivery_fee' => 10.00,
                'status' => 'pending',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_update_any_order(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('siparis.update', $this->otherOrder), [
                'customer_name' => 'Admin Degistirdi',
                'customer_phone' => '05321234567',
                'customer_address' => 'Admin Adres',
                'delivery_fee' => 10.00,
                'status' => 'pending',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
            ]);

        // Should not be 403
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    // =========================================================================
    // Delete (DELETE) IDOR
    // =========================================================================

    /** @test */
    public function bayi_cannot_delete_other_branchs_order(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->delete(route('siparis.destroy', $this->otherOrder));

        $response->assertStatus(403);
        $this->assertDatabaseHas('orders', ['id' => $this->otherOrder->id]);
    }

    /** @test */
    public function admin_can_delete_any_order(): void
    {
        $order = Order::factory()->create([
            'branch_id' => $this->otherBranch->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('siparis.destroy', $order));

        $this->assertNotEquals(403, $response->getStatusCode());
    }

    // =========================================================================
    // Update Status (PATCH) IDOR
    // =========================================================================

    /** @test */
    public function bayi_cannot_update_status_of_other_branchs_order(): void
    {
        $response = $this->actingAs($this->bayiUser)
            ->patch(route('siparis.updateStatus', $this->otherOrder), [
                'status' => 'preparing',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_update_status_of_any_order(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->patch(route('siparis.updateStatus', $this->otherOrder), [
                'status' => 'preparing',
            ]);

        $this->assertNotEquals(403, $response->getStatusCode());
    }

    // =========================================================================
    // Unauthenticated access
    // =========================================================================

    /** @test */
    public function unauthenticated_user_cannot_access_orders(): void
    {
        $response = $this->get(route('siparis.edit', $this->ownOrder));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function unauthenticated_user_cannot_delete_orders(): void
    {
        $response = $this->delete(route('siparis.destroy', $this->ownOrder));
        $response->assertRedirect(route('login'));
    }

    // =========================================================================
    // Isletme user IDOR (branch-scoped access)
    // =========================================================================

    /** @test */
    public function isletme_user_cannot_edit_other_branchs_order(): void
    {
        $isletmeUser = User::factory()->create([
            'role' => 'isletme',
            'roles' => ['isletme'],
            'parent_id' => $this->bayiUser->id,
        ]);
        $isletmeBranch = Branch::factory()->create(['user_id' => $isletmeUser->id]);

        // Order belongs to a different branch
        $otherOrder = Order::factory()->create([
            'branch_id' => $this->otherBranch->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($isletmeUser)
            ->get(route('siparis.edit', $otherOrder));

        $response->assertStatus(403);
    }
}
