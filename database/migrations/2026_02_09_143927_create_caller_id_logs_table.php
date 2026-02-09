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
        Schema::create('caller_id_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone', 20);
            $table->string('device_id', 50)->nullable();
            $table->string('line', 10)->nullable();
            $table->string('device_datetime', 50)->nullable();
            $table->string('str0', 100)->nullable();
            $table->string('str1', 100)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'created_at']);
            $table->index('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caller_id_logs');
    }
};
