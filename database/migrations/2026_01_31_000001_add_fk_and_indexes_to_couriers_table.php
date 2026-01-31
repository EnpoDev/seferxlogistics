<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bug fix: current_order_id FK constraint eksikti
     */
    public function up(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            // current_order_id icin FK constraint ekle (nullable)
            $table->foreign('current_order_id')
                ->references('id')
                ->on('orders')
                ->nullOnDelete();

            // current_order_id icin index ekle (FK otomatik index eklemez)
            $table->index('current_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->dropForeign(['current_order_id']);
            $table->dropIndex(['current_order_id']);
        });
    }
};
