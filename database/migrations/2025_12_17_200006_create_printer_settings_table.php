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
        Schema::create('printer_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('type')->default('receipt'); // kitchen, receipt, label
            $table->string('connection_type')->default('network'); // usb, network, bluetooth
            $table->string('ip_address')->nullable();
            $table->integer('port')->nullable();
            $table->string('model')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_print')->default(true);
            $table->integer('copies')->default(1);
            $table->boolean('print_on_new_order')->default(true);
            $table->boolean('print_on_status_change')->default(false);
            $table->string('paper_width')->default('80mm'); // 58mm, 80mm
            $table->json('settings')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('printer_settings');
    }
};

