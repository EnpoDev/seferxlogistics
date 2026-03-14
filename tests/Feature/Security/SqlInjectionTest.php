<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * SQL Injection prevention tests for AdminReportController.
 * Validates that sort/dir parameters are whitelisted and
 * date parameters are validated against injection.
 */
class SqlInjectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->admin()->create([
            'role' => 'admin',
        ]);
    }

    // =========================================================================
    // Sort parameter injection
    // =========================================================================

    /** @test */
    public function admin_report_rejects_sql_injection_in_sort(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.raporlar.bayi', [
                'sort' => "total_ciro; DROP TABLE orders; --",
            ]));

        $this->assertNotEquals(500, $response->getStatusCode());
        $this->assertTrue(Schema::hasTable('orders'));
    }

    /** @test */
    public function admin_report_rejects_sql_injection_in_dir(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.raporlar.bayi', [
                'sort' => 'total_ciro',
                'dir' => "desc; DROP TABLE orders; --",
            ]));

        $this->assertNotEquals(500, $response->getStatusCode());
        $this->assertTrue(Schema::hasTable('orders'));
    }

    /** @test */
    public function admin_report_accepts_whitelisted_sort_columns(): void
    {
        $validSorts = ['name', 'total_siparis', 'total_ciro', 'isletme_sayisi', 'kurye_sayisi', 'bu_ay_ciro', 'gecen_ay_ciro'];

        foreach ($validSorts as $sort) {
            $response = $this->actingAs($this->adminUser)
                ->get(route('admin.raporlar.bayi', ['sort' => $sort]));

            $this->assertContains($response->getStatusCode(), [200, 302],
                "Sort by '{$sort}' should be accepted");
        }
    }

    /** @test */
    public function admin_report_falls_back_for_invalid_sort_column(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.raporlar.bayi', [
                'sort' => 'nonexistent_column',
            ]));

        // Falls back to default (total_ciro), should not 500
        $this->assertNotEquals(500, $response->getStatusCode());
    }

    // =========================================================================
    // Date parameter injection
    // =========================================================================

    /** @test */
    public function admin_report_rejects_sql_injection_in_start_date(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.raporlar.bayi', [
                'start_date' => "2024-01-01'; DROP TABLE orders; --",
            ]));

        // Should get validation error (302) or gracefully handle
        $this->assertContains($response->getStatusCode(), [302, 422]);
        $this->assertTrue(Schema::hasTable('orders'));
    }

    /** @test */
    public function admin_report_rejects_invalid_date_format(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.raporlar.bayi', [
                'start_date' => 'not-a-date',
                'end_date' => 'also-not-a-date',
            ]));

        $this->assertContains($response->getStatusCode(), [302, 422]);
    }

    /** @test */
    public function admin_report_accepts_valid_date_range(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.raporlar.bayi', [
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
            ]));

        $this->assertContains($response->getStatusCode(), [200, 302]);
    }

    /** @test */
    public function admin_report_rejects_end_date_before_start_date(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.raporlar.bayi', [
                'start_date' => '2024-12-31',
                'end_date' => '2024-01-01',
            ]));

        $this->assertContains($response->getStatusCode(), [302, 422]);
    }

    // =========================================================================
    // Non-admin cannot access admin reports
    // =========================================================================

    /** @test */
    public function bayi_cannot_access_admin_reports(): void
    {
        $bayiUser = User::factory()->bayi()->create([
            'role' => 'bayi',
            'parent_id' => null,
        ]);

        $response = $this->actingAs($bayiUser)
            ->get(route('admin.raporlar.bayi'));

        $this->assertContains($response->getStatusCode(), [302, 403]);
    }
}
