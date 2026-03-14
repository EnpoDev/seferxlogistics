<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courier_meal_shifts', function (Blueprint $table) {
            $table->unsignedBigInteger('restaurant_id')->nullable()->after('courier_id');
        });
    }

    public function down(): void
    {
        Schema::table('courier_meal_shifts', function (Blueprint $table) {
            $table->dropForeign(['restaurant_id']);
            $table->dropColumn('restaurant_id');
        });
    }
};
