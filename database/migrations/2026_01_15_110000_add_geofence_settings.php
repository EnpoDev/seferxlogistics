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
        // Orders tablosuna arrived_at ekle
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('arrived_at')->nullable()->after('on_way_at');
        });

        // Branch settings tablosuna geofence ayarları ekle
        Schema::table('branch_settings', function (Blueprint $table) {
            $table->integer('geofence_radius')->default(50)->after('pool_timeout_minutes');
            $table->boolean('geofence_auto_arrival')->default(true)->after('geofence_radius');
            $table->string('geofence_arrival_message', 255)->nullable()->after('geofence_auto_arrival');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('arrived_at');
        });

        Schema::table('branch_settings', function (Blueprint $table) {
            $table->dropColumn(['geofence_radius', 'geofence_auto_arrival', 'geofence_arrival_message']);
        });
    }
};
