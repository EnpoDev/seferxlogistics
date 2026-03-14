<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courier_meal_benefits', function (Blueprint $table) {
            $table->unique(['courier_id', 'benefit_date', 'meal_type'], 'courier_meal_benefits_unique_per_day');
        });
    }

    public function down(): void
    {
        Schema::table('courier_meal_benefits', function (Blueprint $table) {
            $table->dropUnique('courier_meal_benefits_unique_per_day');
        });
    }
};
