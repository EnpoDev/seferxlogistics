<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('option_group_id');
            $table->foreign('option_group_id')->references('id')->on('product_option_groups')->onDelete('cascade');
            $table->string('name'); // e.g. "Küçük", "Orta", "Büyük", "Sucuk", "Peynir"
            $table->decimal('price_modifier', 8, 2)->default(0); // +/- price adjustment
            $table->boolean('is_default')->default(false);
            $table->boolean('is_available')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index('option_group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_options');
    }
};
