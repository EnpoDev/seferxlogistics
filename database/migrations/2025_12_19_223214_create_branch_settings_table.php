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
        Schema::create('branch_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->unique()->constrained('branches')->onDelete('cascade');
            $table->boolean('courier_enabled')->default(false);
            $table->boolean('balance_tracking')->default(false);
            $table->decimal('current_balance', 10, 2)->default(0);
            $table->boolean('cash_balance_tracking')->default(false);
            $table->decimal('current_cash_balance', 10, 2)->default(0);
            $table->boolean('map_display')->default(true);
            $table->string('nickname')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_settings');
    }
};
