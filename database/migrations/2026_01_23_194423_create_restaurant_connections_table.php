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
        Schema::create('restaurant_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('external_restaurant_id');
            $table->string('external_restaurant_name');
            $table->string('external_platform')->default('seferxyemek');
            $table->string('oauth_client_id')->nullable();
            $table->text('webhook_url')->nullable();
            $table->string('webhook_secret')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('auto_accept')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'external_restaurant_id', 'external_platform'], 'unique_restaurant_connection');
            $table->index(['external_restaurant_id', 'external_platform']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurant_connections');
    }
};
