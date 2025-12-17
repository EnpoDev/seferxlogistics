<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->integer('max_delivery_minutes')->default(45)->after('shifts');
            $table->time('shift_start')->nullable()->after('max_delivery_minutes');
            $table->time('shift_end')->nullable()->after('shift_start');
            $table->boolean('notification_enabled')->default(true)->after('shift_end');
            $table->integer('active_orders_count')->default(0)->after('notification_enabled');
            $table->integer('total_deliveries')->default(0)->after('active_orders_count');
            $table->decimal('average_delivery_time', 8, 2)->nullable()->after('total_deliveries'); // in minutes
        });
    }

    public function down(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->dropColumn([
                'max_delivery_minutes',
                'shift_start',
                'shift_end',
                'notification_enabled',
                'active_orders_count',
                'total_deliveries',
                'average_delivery_time'
            ]);
        });
    }
};

