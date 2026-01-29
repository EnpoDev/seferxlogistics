<?php

namespace Tests\Unit\Services;

use App\Models\Branch;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use App\Services\FinancialReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FinancialReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FinancialReportService $financialService;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->financialService = new FinancialReportService();
        $this->branch = Branch::factory()->create();
    }

    /** @test */
    public function it_calculates_financial_summary(): void
    {
        // Create delivered orders
        $this->createOrder(['total' => 100, 'status' => 'delivered', 'payment_method' => 'cash']);
        $this->createOrder(['total' => 150, 'status' => 'delivered', 'payment_method' => 'card']);
        $this->createOrder(['total' => 200, 'status' => 'delivered', 'payment_method' => 'cash']);

        // Create non-delivered order (should not be counted)
        $this->createOrder(['total' => 300, 'status' => 'pending']);

        $summary = $this->financialService->getFinancialSummary(
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        $this->assertEquals(450, $summary['revenue']['total']);
        $this->assertEquals(3, $summary['orders']['count']);
        $this->assertArrayHasKey('payment_breakdown', $summary);
    }

    /** @test */
    public function it_filters_financial_summary_by_branch(): void
    {
        $otherBranch = Branch::factory()->create();

        $this->createOrder(['total' => 100, 'status' => 'delivered', 'branch_id' => $this->branch->id]);
        $this->createOrder(['total' => 200, 'status' => 'delivered', 'branch_id' => $otherBranch->id]);

        $summary = $this->financialService->getFinancialSummary(
            now()->startOfMonth(),
            now()->endOfMonth(),
            $this->branch->id
        );

        $this->assertEquals(100, $summary['revenue']['total']);
        $this->assertEquals(1, $summary['orders']['count']);
    }

    /** @test */
    public function it_calculates_daily_revenue_report(): void
    {
        // Create orders on different days
        $this->createOrder([
            'total' => 100,
            'status' => 'delivered',
            'created_at' => now()->subDays(2),
        ]);
        $this->createOrder([
            'total' => 150,
            'status' => 'delivered',
            'created_at' => now()->subDays(1),
        ]);
        $this->createOrder([
            'total' => 200,
            'status' => 'delivered',
            'created_at' => now(),
        ]);

        $report = $this->financialService->getDailyRevenueReport(
            now()->subDays(7),
            now()
        );

        $this->assertCount(3, $report);
        $this->assertArrayHasKey('date', $report->first());
        $this->assertArrayHasKey('order_count', $report->first());
        $this->assertArrayHasKey('total_revenue', $report->first());
    }

    /** @test */
    public function it_calculates_courier_earnings(): void
    {
        $courier = Courier::factory()->create();

        $this->createOrder([
            'courier_id' => $courier->id,
            'delivery_fee' => 10,
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
        $this->createOrder([
            'courier_id' => $courier->id,
            'delivery_fee' => 15,
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        $report = $this->financialService->getCourierEarningsReport(
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        $this->assertCount(1, $report);
        $this->assertEquals(25, $report->first()['total_earnings']);
        $this->assertEquals(2, $report->first()['delivery_count']);
    }

    /** @test */
    public function it_calculates_branch_performance(): void
    {
        $branch2 = Branch::factory()->create();

        $this->createOrder(['branch_id' => $this->branch->id, 'total' => 100, 'status' => 'delivered']);
        $this->createOrder(['branch_id' => $this->branch->id, 'total' => 150, 'status' => 'delivered']);
        $this->createOrder(['branch_id' => $branch2->id, 'total' => 200, 'status' => 'delivered']);

        $report = $this->financialService->getBranchPerformanceReport(
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        $this->assertCount(2, $report);
        $this->assertArrayHasKey('branch_name', $report->first());
        $this->assertArrayHasKey('order_count', $report->first());
        $this->assertArrayHasKey('total_revenue', $report->first());
    }

    /** @test */
    public function it_calculates_hourly_distribution(): void
    {
        // Create orders at different hours
        $this->createOrder([
            'status' => 'delivered',
            'created_at' => now()->setTime(12, 0),
        ]);
        $this->createOrder([
            'status' => 'delivered',
            'created_at' => now()->setTime(12, 30),
        ]);
        $this->createOrder([
            'status' => 'delivered',
            'created_at' => now()->setTime(18, 0),
        ]);

        $distribution = $this->financialService->getHourlyDistribution(
            now()->startOfDay(),
            now()->endOfDay()
        );

        $this->assertCount(24, $distribution);
        $this->assertEquals(2, $distribution[12]['count']);
        $this->assertEquals(1, $distribution[18]['count']);
    }

    /** @test */
    public function it_calculates_weekly_comparison(): void
    {
        // Create orders this week
        $this->createOrder([
            'total' => 100,
            'status' => 'delivered',
            'created_at' => now(),
        ]);

        // Create orders last week
        $this->createOrder([
            'total' => 80,
            'status' => 'delivered',
            'created_at' => now()->subWeek(),
        ]);

        $comparison = $this->financialService->getWeeklyComparison();

        $this->assertArrayHasKey('this_week', $comparison);
        $this->assertArrayHasKey('last_week', $comparison);
        $this->assertArrayHasKey('changes', $comparison);
        $this->assertArrayHasKey('revenue_percent', $comparison['changes']);
        $this->assertArrayHasKey('revenue_trend', $comparison['changes']);
    }

    /** @test */
    public function it_calculates_monthly_comparison(): void
    {
        // Create orders this month
        $this->createOrder([
            'total' => 200,
            'status' => 'delivered',
            'created_at' => now(),
        ]);

        // Create orders last month
        $this->createOrder([
            'total' => 150,
            'status' => 'delivered',
            'created_at' => now()->subMonth(),
        ]);

        $comparison = $this->financialService->getMonthlyComparison();

        $this->assertArrayHasKey('this_month', $comparison);
        $this->assertArrayHasKey('last_month', $comparison);
        $this->assertArrayHasKey('changes', $comparison);
    }

    /** @test */
    public function it_calculates_cash_flow(): void
    {
        $this->createOrder([
            'total' => 100,
            'delivery_fee' => 10,
            'payment_method' => 'cash',
            'status' => 'delivered',
        ]);
        $this->createOrder([
            'total' => 150,
            'delivery_fee' => 15,
            'payment_method' => 'card',
            'status' => 'delivered',
        ]);

        $cashFlow = $this->financialService->getCashFlowReport(
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        $this->assertArrayHasKey('income', $cashFlow);
        $this->assertArrayHasKey('expenses', $cashFlow);
        $this->assertArrayHasKey('net_cash_flow', $cashFlow);
        $this->assertEquals(100, $cashFlow['income']['cash']);
        $this->assertEquals(150, $cashFlow['income']['card']);
    }

    /** @test */
    public function it_prepares_export_data(): void
    {
        $this->createOrder(['total' => 100, 'status' => 'delivered']);

        $exportData = $this->financialService->prepareExportData(
            'summary',
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        $this->assertArrayHasKey('title', $exportData);
        $this->assertArrayHasKey('data', $exportData);
        $this->assertEquals('Finansal Özet Raporu', $exportData['title']);
    }

    /** @test */
    public function it_handles_empty_data_gracefully(): void
    {
        $summary = $this->financialService->getFinancialSummary(
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        $this->assertEquals(0, $summary['revenue']['total']);
        $this->assertEquals(0, $summary['orders']['count']);
        $this->assertEquals(0, $summary['orders']['average_value']);
    }

    /** @test */
    public function it_calculates_payment_breakdown(): void
    {
        $this->createOrder(['total' => 100, 'status' => 'delivered', 'payment_method' => 'cash']);
        $this->createOrder(['total' => 150, 'status' => 'delivered', 'payment_method' => 'cash']);
        $this->createOrder(['total' => 200, 'status' => 'delivered', 'payment_method' => 'card']);
        $this->createOrder(['total' => 120, 'status' => 'delivered', 'payment_method' => 'online']);

        $summary = $this->financialService->getFinancialSummary(
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        $breakdown = $summary['payment_breakdown'];

        $this->assertEquals(2, $breakdown['cash']['count']);
        $this->assertEquals(250, $breakdown['cash']['total']);
        $this->assertEquals(1, $breakdown['card']['count']);
        $this->assertEquals(200, $breakdown['card']['total']);
    }

    /**
     * Create a test order
     */
    protected function createOrder(array $attributes = []): Order
    {
        $customer = Customer::factory()->create();

        return Order::factory()->create(array_merge([
            'branch_id' => $attributes['branch_id'] ?? $this->branch->id,
            'customer_id' => $customer->id,
            'subtotal' => ($attributes['total'] ?? 100) - ($attributes['delivery_fee'] ?? 10),
            'delivery_fee' => $attributes['delivery_fee'] ?? 10,
            'total' => $attributes['total'] ?? 100,
            'payment_method' => $attributes['payment_method'] ?? 'cash',
            'status' => $attributes['status'] ?? 'pending',
        ], $attributes));
    }
}
