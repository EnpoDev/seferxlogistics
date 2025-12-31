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
        Schema::create('pricing_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
            $table->enum('type', ['business', 'courier']);
            $table->enum('policy_type', [
                'fixed',                    // Sabit Fiyat & Sabit Yüzdelik
                'package_based',            // Paket Tutarına Göre
                'distance_based',           // Teslimat Mesafesine Göre
                'periodic',                 // Periyodik
                'unit_price',              // Teslimat Mesafesi Birim Fiyat
                'consecutive_discount'     // Ardışık Paket İndirimi
            ]);
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_policies');
    }
};
