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
            $table->string('external_order_id')->nullable()->after('order_number');
            $table->foreignId('restaurant_connection_id')->nullable()->after('user_id')
                  ->constrained('restaurant_connections')->nullOnDelete();

            $table->unique(['external_order_id', 'platform'], 'unique_external_order');
            $table->index('restaurant_connection_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['restaurant_connection_id']);
            $table->dropUnique('unique_external_order');
            $table->dropColumn(['external_order_id', 'restaurant_connection_id']);
        });
    }
};
