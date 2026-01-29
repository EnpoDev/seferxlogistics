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
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->enum('caller_type', ['customer', 'courier']);
            $table->string('from_number');
            $table->string('to_number');
            $table->string('proxy_number')->nullable();
            $table->string('external_call_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration')->nullable(); // saniye
            $table->enum('status', ['initiated', 'ringing', 'answered', 'completed', 'missed', 'failed', 'busy'])->default('initiated');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'caller_type']);
            $table->index('external_call_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
