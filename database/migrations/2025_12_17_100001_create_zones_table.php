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
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color')->default('#3B82F6');
            $table->json('coordinates')->nullable(); // GeoJSON polygon coordinates
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('delivery_fee', 8, 2)->default(0);
            $table->integer('estimated_delivery_minutes')->default(30);
            $table->timestamps();
        });

        // Pivot table for zone-courier many-to-many relationship
        Schema::create('courier_zone', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_id')->constrained()->onDelete('cascade');
            $table->foreignId('zone_id')->constrained()->onDelete('cascade');
            $table->boolean('is_primary')->default(false); // Primary zone for the courier
            $table->timestamps();
            
            $table->unique(['courier_id', 'zone_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_zone');
        Schema::dropIfExists('zones');
    }
};

