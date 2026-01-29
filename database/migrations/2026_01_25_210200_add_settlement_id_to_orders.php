<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Siparişlerin hangi settlement'a dahil edildiğini takip eder.
     * Bir sipariş sadece bir settlement'a dahil olabilir.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('settlement_id')
                ->nullable()
                ->after('restaurant_connection_id')
                ->constrained('daily_settlements')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['settlement_id']);
            $table->dropColumn('settlement_id');
        });
    }
};
