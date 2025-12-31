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
        Schema::create('theme_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('theme_mode')->default('system'); // light, dark, system
            $table->boolean('compact_mode')->default(false);
            $table->boolean('animations_enabled')->default(true);
            $table->boolean('sidebar_auto_hide')->default(true);
            $table->string('sidebar_width')->default('normal'); // narrow, normal, wide
            $table->string('accent_color')->nullable();
            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_settings');
    }
};

