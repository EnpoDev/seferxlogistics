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
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // yemeksepeti, getir, trendyol
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('credentials')->nullable(); // API keys, tokens etc.
            $table->json('settings')->nullable(); // Platform-specific settings
            $table->boolean('is_active')->default(false);
            $table->boolean('is_connected')->default(false);
            $table->timestamp('last_sync_at')->nullable();
            $table->string('status')->default('inactive'); // inactive, connecting, connected, error
            $table->text('error_message')->nullable();
            $table->string('webhook_url')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->timestamps();
            
            $table->unique('platform');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};

