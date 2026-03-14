<?php

namespace Tests\Feature\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOptionGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Product Variation (Option Groups) API
 * - GET /urunler/{product}/varyasyonlar → returns groups
 * - POST /urunler/{product}/varyasyonlar → stores groups (full replace)
 * - Validation rules for groups payload
 */
class ProductVariationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'bayi',
            'roles' => ['bayi'],
        ]);

        $category = Category::create([
            'name' => 'Test Kategori',
            'slug' => 'test-kategori',
            'is_active' => true,
        ]);

        $this->product = Product::factory()->create([
            'category_id' => $category->id,
        ]);
    }

    // =========================================================================
    // GET option groups
    // =========================================================================

    /** @test */
    public function get_option_groups_returns_empty_for_new_product(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('urun.varyasyonlar', $this->product));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(0, 'groups');
    }

    /** @test */
    public function get_option_groups_returns_existing_groups_with_options(): void
    {
        $group = $this->product->optionGroups()->create([
            'name' => 'Porsiyon',
            'type' => 'radio',
            'required' => true,
            'order' => 0,
        ]);

        $group->options()->create([
            'name' => 'Küçük',
            'price_modifier' => 0,
            'order' => 0,
        ]);

        $group->options()->create([
            'name' => 'Büyük',
            'price_modifier' => 10,
            'order' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('urun.varyasyonlar', $this->product));

        $response->assertStatus(200)
            ->assertJsonCount(1, 'groups')
            ->assertJsonPath('groups.0.name', 'Porsiyon')
            ->assertJsonCount(2, 'groups.0.options');
    }

    // =========================================================================
    // POST store option groups
    // =========================================================================

    /** @test */
    public function store_option_groups_with_valid_payload(): void
    {
        $payload = [
            'groups' => [
                [
                    'name' => 'Porsiyon',
                    'type' => 'radio',
                    'required' => true,
                    'options' => [
                        ['name' => 'Küçük', 'price_modifier' => 0],
                        ['name' => 'Orta', 'price_modifier' => 5],
                        ['name' => 'Büyük', 'price_modifier' => 10],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('urun.varyasyonlar.kaydet', $this->product), $payload);

        $response->assertStatus(200);

        $this->assertDatabaseCount('product_option_groups', 1);
        $this->assertDatabaseHas('product_option_groups', [
            'product_id' => $this->product->id,
            'name' => 'Porsiyon',
            'type' => 'radio',
        ]);

        $group = ProductOptionGroup::where('product_id', $this->product->id)->first();
        $this->assertCount(3, $group->options);
    }

    /** @test */
    public function store_replaces_existing_groups_on_second_post(): void
    {
        // First POST: create initial groups
        $payload1 = [
            'groups' => [
                [
                    'name' => 'Eski Grup',
                    'type' => 'radio',
                    'options' => [
                        ['name' => 'Seçenek A'],
                    ],
                ],
                [
                    'name' => 'Eski Grup 2',
                    'type' => 'checkbox',
                    'options' => [
                        ['name' => 'Ekstra 1'],
                    ],
                ],
            ],
        ];

        $this->actingAs($this->user)
            ->postJson(route('urun.varyasyonlar.kaydet', $this->product), $payload1)
            ->assertStatus(200);

        $this->assertDatabaseCount('product_option_groups', 2);

        // Second POST: full replace
        $payload2 = [
            'groups' => [
                [
                    'name' => 'Yeni Grup',
                    'type' => 'checkbox',
                    'options' => [
                        ['name' => 'Yeni Seçenek'],
                    ],
                ],
            ],
        ];

        $this->actingAs($this->user)
            ->postJson(route('urun.varyasyonlar.kaydet', $this->product), $payload2)
            ->assertStatus(200);

        // Old groups should be deleted, only new group remains
        $this->assertDatabaseCount('product_option_groups', 1);
        $this->assertDatabaseHas('product_option_groups', [
            'product_id' => $this->product->id,
            'name' => 'Yeni Grup',
        ]);
        $this->assertDatabaseMissing('product_option_groups', [
            'name' => 'Eski Grup',
        ]);
    }

    // =========================================================================
    // Validation
    // =========================================================================

    /** @test */
    public function validation_groups_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('urun.varyasyonlar.kaydet', $this->product), [], [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(422);
        $details = $response->json('error.details');
        $this->assertArrayHasKey('groups', $details);
    }

    /** @test */
    public function validation_group_type_must_be_radio_or_checkbox(): void
    {
        $payload = [
            'groups' => [
                [
                    'name' => 'Test',
                    'type' => 'dropdown',
                    'options' => [
                        ['name' => 'Opt 1'],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('urun.varyasyonlar.kaydet', $this->product), $payload, [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(422);
        $details = $response->json('error.details');
        $this->assertArrayHasKey('groups.0.type', $details);
    }

    /** @test */
    public function validation_group_options_must_have_at_least_one(): void
    {
        $payload = [
            'groups' => [
                [
                    'name' => 'Test',
                    'type' => 'radio',
                    'options' => [],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('urun.varyasyonlar.kaydet', $this->product), $payload, [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(422);
        $details = $response->json('error.details');
        $this->assertArrayHasKey('groups.0.options', $details);
    }

    /** @test */
    public function validation_group_name_is_required(): void
    {
        $payload = [
            'groups' => [
                [
                    'type' => 'radio',
                    'options' => [
                        ['name' => 'Opt 1'],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('urun.varyasyonlar.kaydet', $this->product), $payload, [
                'Accept' => 'application/json',
            ]);

        $response->assertStatus(422);
        $details = $response->json('error.details');
        $this->assertArrayHasKey('groups.0.name', $details);
    }

    // =========================================================================
    // Unauthenticated access
    // =========================================================================

    /** @test */
    public function unauthenticated_user_cannot_access_variations(): void
    {
        $response = $this->getJson(route('urun.varyasyonlar', $this->product));
        $response->assertStatus(401);
    }
}
