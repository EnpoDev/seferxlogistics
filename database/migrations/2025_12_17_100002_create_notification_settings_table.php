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
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Order notifications
            $table->boolean('new_order_notification')->default(true);
            $table->boolean('order_status_notification')->default(true);
            $table->boolean('order_cancelled_notification')->default(true);
            
            // Email notifications
            $table->boolean('email_daily_summary')->default(true);
            $table->boolean('email_weekly_report')->default(false);
            $table->boolean('email_new_order')->default(false);
            
            // Push notifications
            $table->boolean('push_enabled')->default(true);
            $table->boolean('push_new_order')->default(true);
            $table->boolean('push_order_status')->default(true);
            
            // SMS notifications
            $table->boolean('sms_enabled')->default(false);
            $table->boolean('sms_new_order')->default(false);
            
            // Sound settings
            $table->boolean('sound_enabled')->default(true);
            $table->string('notification_sound')->default('default');
            
            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};

