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
            $table->json('break_durations')->nullable()->after('shifts');
        });

        Schema::table('business_info', function (Blueprint $table) {
            $table->integer('default_break_duration')->default(60)->after('default_shifts');
            $table->integer('default_break_parts')->default(2)->after('default_break_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->dropColumn('break_durations');
        });

        Schema::table('business_info', function (Blueprint $table) {
            $table->dropColumn(['default_break_duration', 'default_break_parts']);
        });
    }
};
