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
            $table->boolean('auto_assign_courier')->default(false)->after('pool_notify_couriers');
            $table->boolean('check_courier_shift')->default(true)->after('auto_assign_courier');
            $table->integer('max_delivery_time')->default(45)->after('check_courier_shift'); // dakika
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_settings', function (Blueprint $table) {
            $table->dropColumn([
                'auto_assign_courier',
                'check_courier_shift',
                'max_delivery_time',
            ]);
        });
    }
};
