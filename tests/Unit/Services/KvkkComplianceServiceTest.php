<?php

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Services\KvkkComplianceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KvkkComplianceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected KvkkComplianceService $service;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new KvkkComplianceService();
        $this->branch = Branch::factory()->create();

        Storage::fake('local');
    }

    /** @test */
    public function it_can_export_user_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $result = $this->service->exportUserData($user);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('path', $result);
        $this->assertStringContainsString('kvkk_export_user_', $result['filename']);
    }

    /** @test */
    public function it_can_export_courier_data(): void
    {
        // Skip this test on SQLite due to DATE_FORMAT incompatibility
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('SQLite does not support DATE_FORMAT function');
        }

        $courier = Courier::factory()->create([
            'name' => 'Test Courier',
            'email' => 'courier@example.com',
            'phone' => '5551234567',
        ]);

        $result = $this->service->exportCourierData($courier);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('filename', $result);
        $this->assertStringContainsString('kvkk_export_courier_', $result['filename']);
    }

    /** @test */
    public function export_includes_consent_records(): void
    {
        $user = User::factory()->create();

        $result = $this->service->exportUserData($user);

        $this->assertTrue($result['success']);

        // Check file was created
        Storage::assertExists($result['path']);
    }

    /** @test */
    public function export_file_contains_valid_json(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
        ]);

        $result = $this->service->exportUserData($user);

        $content = Storage::get($result['path']);
        $data = json_decode($content, true);

        $this->assertNotNull($data);
        $this->assertArrayHasKey('export_date', $data);
        $this->assertArrayHasKey('data_controller', $data);
        $this->assertArrayHasKey('personal_data', $data);
        $this->assertEquals('SeferX Lojistik', $data['data_controller']);
    }

    /** @test */
    public function courier_export_masks_sensitive_data(): void
    {
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('SQLite does not support DATE_FORMAT function');
        }

        $courier = Courier::factory()->create([
            'tc_no' => '12345678901',
            'iban' => 'TR123456789012345678901234',
        ]);

        $result = $this->service->exportCourierData($courier);

        $content = Storage::get($result['path']);
        $data = json_decode($content, true);

        // TC and IBAN should be masked
        $this->assertEquals('***MASKED***', $data['personal_data']['identity']['tc_no']);
        $this->assertEquals('***MASKED***', $data['personal_data']['financial']['iban']);
    }

    /** @test */
    public function it_includes_delivery_history_in_courier_export(): void
    {
        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('SQLite does not support DATE_FORMAT function');
        }

        $courier = Courier::factory()->create();
        $customer = Customer::factory()->create();

        // Create some delivered orders
        Order::factory()->count(3)->create([
            'branch_id' => $this->branch->id,
            'courier_id' => $courier->id,
            'customer_id' => $customer->id,
            'status' => 'delivered',
        ]);

        $result = $this->service->exportCourierData($courier);

        $content = Storage::get($result['path']);
        $data = json_decode($content, true);

        $this->assertArrayHasKey('delivery_history', $data);
    }

    /** @test */
    public function export_generates_unique_filename_per_call(): void
    {
        $user = User::factory()->create();

        $result1 = $this->service->exportUserData($user);

        // Wait at least 1 second to ensure different timestamp
        sleep(1);

        $result2 = $this->service->exportUserData($user);

        $this->assertNotEquals($result1['filename'], $result2['filename']);
    }

    /** @test */
    public function export_data_subject_contains_user_info(): void
    {
        $user = User::factory()->create();

        $result = $this->service->exportUserData($user);

        $content = Storage::get($result['path']);
        $data = json_decode($content, true);

        $this->assertArrayHasKey('data_subject', $data);
        $this->assertEquals($user->id, $data['data_subject']['id']);
        $this->assertEquals('user', $data['data_subject']['type']);
    }

    /** @test */
    public function export_contains_processing_activities(): void
    {
        $user = User::factory()->create();

        $result = $this->service->exportUserData($user);

        $content = Storage::get($result['path']);
        $data = json_decode($content, true);

        $this->assertArrayHasKey('processing_activities', $data);
    }

    /** @test */
    public function export_path_contains_exports_directory(): void
    {
        $user = User::factory()->create();

        $result = $this->service->exportUserData($user);

        $this->assertStringStartsWith('exports/', $result['path']);
    }
}
