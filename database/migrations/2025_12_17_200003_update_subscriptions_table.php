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
        // Drop and recreate the subscriptions table with proper structure
        Schema::dropIfExists('subscriptions');
        
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained()->onDelete('restrict');
            $table->foreignId('payment_card_id')->nullable()->constrained()->onDelete('set null');
            $table->string('status')->default('pending'); // active, cancelled, expired, pending, trial, past_due
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('next_billing_date')->nullable();
            $table->timestamp('last_payment_date')->nullable();
            $table->decimal('last_payment_amount', 10, 2)->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('next_billing_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
        
        // Recreate original empty table
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }
};

