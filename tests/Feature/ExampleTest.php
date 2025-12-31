<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Unauthenticated users are redirected to login.
     */
    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    /**
     * Authenticated users can access the application.
     */
    public function test_authenticated_users_can_access_application(): void
    {
        $user = User::factory()->create(['roles' => ['bayi']]);

        $response = $this->actingAs($user)->get('/');

        // Should redirect to panel selection or dashboard
        $response->assertStatus(302);
    }
}
