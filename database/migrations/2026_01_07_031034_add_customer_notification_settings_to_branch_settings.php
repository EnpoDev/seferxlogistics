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
        Schema::table('branch_settings', function (Blueprint $table) {
            // Müşteri bildirim ayarları
            $table->boolean('customer_sms_enabled')->default(false);
            $table->boolean('customer_whatsapp_enabled')->default(false);
            $table->boolean('notify_on_confirmed')->default(true);
            $table->boolean('notify_on_preparing')->default(false);
            $table->boolean('notify_on_ready')->default(false);
            $table->boolean('notify_on_courier_assigned')->default(true);
            $table->boolean('notify_on_picked_up')->default(true);
            $table->boolean('notify_on_delivered')->default(true);
            $table->boolean('notify_on_cancelled')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_settings', function (Blueprint $table) {
            $table->dropColumn([
                'customer_sms_enabled',
                'customer_whatsapp_enabled',
                'notify_on_confirmed',
                'notify_on_preparing',
                'notify_on_ready',
                'notify_on_courier_assigned',
                'notify_on_picked_up',
                'notify_on_delivered',
                'notify_on_cancelled',
            ]);
        });
    }
};
