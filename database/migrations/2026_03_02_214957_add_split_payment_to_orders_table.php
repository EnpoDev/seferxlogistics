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
        Schema::table('orders', function (Blueprint $table) {
            // Add JSON column for split payment support
            // Structure: [{"method": "cash", "amount": 500}, {"method": "card", "amount": 300}]
            $table->json('payment_methods')->nullable()->after('payment_method');

            // Keep old payment_method for backward compatibility
            // New orders will use payment_methods, old ones keep payment_method
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_methods');
        });
    }
};
