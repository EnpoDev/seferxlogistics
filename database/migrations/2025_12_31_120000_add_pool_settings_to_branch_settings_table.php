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
        Schema::table('branch_settings', function (Blueprint $table) {
            $table->boolean('pool_enabled')->default(false)->after('map_display');
            $table->integer('pool_wait_time')->default(5)->after('pool_enabled'); // dakika
            $table->boolean('pool_auto_assign')->default(false)->after('pool_wait_time');
            $table->integer('pool_max_orders')->default(10)->after('pool_auto_assign');
            $table->boolean('pool_priority_by_distance')->default(true)->after('pool_max_orders');
            $table->boolean('pool_notify_couriers')->default(true)->after('pool_priority_by_distance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_settings', function (Blueprint $table) {
            $table->dropColumn([
                'pool_enabled',
                'pool_wait_time',
                'pool_auto_assign',
                'pool_max_orders',
                'pool_priority_by_distance',
                'pool_notify_couriers',
            ]);
        });
    }
};
