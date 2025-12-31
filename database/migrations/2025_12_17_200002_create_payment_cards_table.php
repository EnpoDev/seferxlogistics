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
        Schema::create('payment_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('card_holder_name');
            $table->string('card_number_last4', 4);
            $table->string('card_brand')->default('unknown'); // visa, mastercard, amex, troy
            $table->tinyInteger('expiry_month');
            $table->smallInteger('expiry_year');
            $table->boolean('is_default')->default(false);
            $table->text('token')->nullable(); // Payment gateway token (encrypted)
            $table->string('gateway')->nullable(); // iyzico, paytr, stripe
            $table->timestamps();
            
            $table->index(['user_id', 'is_default']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_cards');
    }
};

