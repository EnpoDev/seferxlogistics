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
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('accept_cash')->default(true);
            $table->boolean('accept_card')->default(true);
            $table->boolean('accept_card_on_delivery')->default(false);
            $table->boolean('accept_online')->default(false);
            $table->string('payment_provider')->nullable(); // iyzico, paytr, stripe
            $table->json('provider_settings')->nullable();
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->decimal('max_cash_amount', 10, 2)->nullable();
            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};

