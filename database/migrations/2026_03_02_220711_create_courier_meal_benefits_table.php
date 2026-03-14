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
        Schema::create('courier_meal_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_id')->constrained('couriers')->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->date('benefit_date');
            $table->enum('meal_type', ['breakfast', 'lunch', 'dinner']);
            $table->decimal('meal_value', 8, 2)->default(0);
            $table->boolean('is_used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Index for efficient queries
            $table->index(['courier_id', 'benefit_date']);
            $table->index(['branch_id', 'benefit_date']);
            $table->index(['is_used']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_meal_benefits');
    }
};
