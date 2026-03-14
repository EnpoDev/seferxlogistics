<?php

namespace Tests\Unit\Models;

use App\Models\Branch;
use App\Models\Courier;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Order::updateCourierCashBalance()
 * - Split payment: only cash portion increments courier balance
 * - Full cash payment: total amount increments balance
 * - Non-cash payment: balance unchanged
 * - Split payment with zero cash: balance unchanged
 */
class OrderCashBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected Courier $courier;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create(['role' => 'bayi', 'roles' => ['bayi']]);
        $this->branch = Branch::factory()->create(['user_id' => $user->id]);

        $this->courier = Courier::factory()->create([
            'user_id' => $user->id,
            'cash_balance' => 0,
        ]);
    }

    /** @test */
    public function split_payment_only_increments_cash_portion(): void
    {
        $order = Order::factory()->delivered()->create([
            'branch_id' => $this->branch->id,
            'courier_id' => $this->courier->id,
            'total' => 150,
            'payment_method' => 'cash',
            'payment_methods' => [
                ['method' => 'cash', 'amount' => 100],
                ['method' => 'card', 'amount' => 50],
            ],
        ]);

        $order->updateCourierCashBalance();

        $this->courier->refresh();
        $this->assertEquals(100.00, (float) $this->courier->cash_balance);
    }

    /** @test */
    public function full_cash_payment_increments_full_total(): void
    {
        $order = Order::factory()->delivered()->create([
            'branch_id' => $this->branch->id,
            'courier_id' => $this->courier->id,
            'total' => 200,
            'payment_method' => Order::PAYMENT_CASH,
            'payment_methods' => null,
        ]);

        $order->updateCourierCashBalance();

        $this->courier->refresh();
        $this->assertEquals(200.00, (float) $this->courier->cash_balance);
    }

    /** @test */
    public function non_cash_payment_does_not_change_balance(): void
    {
        $order = Order::factory()->delivered()->create([
            'branch_id' => $this->branch->id,
            'courier_id' => $this->courier->id,
            'total' => 150,
            'payment_method' => Order::PAYMENT_CARD,
            'payment_methods' => null,
        ]);

        $order->updateCourierCashBalance();

        $this->courier->refresh();
        $this->assertEquals(0.00, (float) $this->courier->cash_balance);
    }

    /** @test */
    public function split_payment_with_zero_cash_does_not_change_balance(): void
    {
        $order = Order::factory()->delivered()->create([
            'branch_id' => $this->branch->id,
            'courier_id' => $this->courier->id,
            'total' => 150,
            'payment_method' => 'card',
            'payment_methods' => [
                ['method' => 'card', 'amount' => 100],
                ['method' => 'pluxee', 'amount' => 50],
            ],
        ]);

        $order->updateCourierCashBalance();

        $this->courier->refresh();
        $this->assertEquals(0.00, (float) $this->courier->cash_balance);
    }

    /** @test */
    public function non_delivered_order_does_not_update_balance(): void
    {
        $order = Order::factory()->create([
            'branch_id' => $this->branch->id,
            'courier_id' => $this->courier->id,
            'total' => 200,
            'payment_method' => Order::PAYMENT_CASH,
            'status' => Order::STATUS_PREPARING,
        ]);

        $order->updateCourierCashBalance();

        $this->courier->refresh();
        $this->assertEquals(0.00, (float) $this->courier->cash_balance);
    }

    /** @test */
    public function order_without_courier_does_not_update_balance(): void
    {
        $order = Order::factory()->delivered()->create([
            'branch_id' => $this->branch->id,
            'courier_id' => null,
            'total' => 200,
            'payment_method' => Order::PAYMENT_CASH,
        ]);

        // Should not throw an error
        $order->updateCourierCashBalance();

        $this->assertTrue(true);
    }
}
