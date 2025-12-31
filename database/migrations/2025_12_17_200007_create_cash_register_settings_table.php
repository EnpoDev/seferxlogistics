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
        Schema::create('cash_register_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('is_enabled')->default(false);
            $table->string('model')->nullable(); // hugin, olivetti, ingenico, custom
            $table->string('connection_type')->default('serial'); // serial, ethernet, usb
            $table->string('port')->nullable(); // COM port or IP address
            $table->integer('baud_rate')->nullable();
            $table->integer('default_vat_rate')->default(20);
            $table->boolean('auto_send_orders')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_register_settings');
    }
};

