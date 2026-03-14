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
            $table->string('caller_id_device_id', 50)->nullable()->after('geofence_arrival_message');
            $table->boolean('caller_id_enabled')->default(false)->after('caller_id_device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_settings', function (Blueprint $table) {
            $table->dropColumn(['caller_id_device_id', 'caller_id_enabled']);
        });
    }
};
