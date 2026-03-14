<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            // Payment method configurations
            $table->boolean('payment_cash_enabled')->default(true)->after('is_active');
            $table->boolean('payment_card_enabled')->default(true)->after('payment_cash_enabled');
            $table->boolean('payment_online_enabled')->default(false)->after('payment_card_enabled');
            $table->boolean('payment_bank_transfer_enabled')->default(false)->after('payment_online_enabled');
            $table->boolean('payment_meal_cards_enabled')->default(false)->after('payment_bank_transfer_enabled');

            // Which specific meal cards are accepted (JSON array: ['sodexo', 'multinet', 'ticket', etc])
            $table->json('enabled_meal_cards')->nullable()->after('payment_meal_cards_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'payment_cash_enabled',
                'payment_card_enabled',
                'payment_online_enabled',
                'payment_bank_transfer_enabled',
                'payment_meal_cards_enabled',
                'enabled_meal_cards'
            ]);
        });
    }
};
