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
        Schema::create('application_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('language', 5)->default('tr');
            $table->string('timezone')->default('Europe/Istanbul');
            $table->string('currency', 3)->default('TRY');
            $table->boolean('auto_accept_orders')->default(false);
            $table->boolean('sound_notifications')->default(true);
            $table->integer('default_order_timeout')->default(30); // minutes
            $table->integer('default_preparation_time')->default(20); // minutes
            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_settings');
    }
};

