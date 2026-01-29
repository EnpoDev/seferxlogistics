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
        Schema::table('couriers', function (Blueprint $table) {
            $table->tinyInteger('battery_level')->nullable()->after('lng');
            $table->boolean('is_charging')->default(false)->after('battery_level');
            $table->timestamp('battery_updated_at')->nullable()->after('is_charging');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->dropColumn(['battery_level', 'is_charging', 'battery_updated_at']);
        });
    }
};
