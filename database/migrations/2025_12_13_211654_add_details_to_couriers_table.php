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
            $table->string('photo_path')->nullable()->after('email');
            $table->string('tc_no')->nullable()->after('photo_path');
            $table->string('vehicle_plate')->nullable()->after('tc_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'tc_no', 'vehicle_plate']);
        });
    }
};
