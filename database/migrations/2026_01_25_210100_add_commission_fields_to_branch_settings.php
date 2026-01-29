<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Restoran komisyon oranı ve kurye teslimat payı alanlarını ekler.
     */
    public function up(): void
    {
        Schema::table('branch_settings', function (Blueprint $table) {
            // Restoran siparişlerinden alınacak komisyon oranı (%)
            $table->decimal('restaurant_commission_rate', 5, 2)->default(5.00)->after('current_cash_balance');

            // Teslimat ücretinden kuryeye verilecek oran (%)
            $table->decimal('courier_fee_percentage', 5, 2)->default(60.00)->after('restaurant_commission_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_settings', function (Blueprint $table) {
            $table->dropColumn(['restaurant_commission_rate', 'courier_fee_percentage']);
        });
    }
};
