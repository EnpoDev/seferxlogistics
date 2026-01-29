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
        Schema::table('orders', function (Blueprint $table) {
            // Sadece mevcut olmayan kolonları ekle
            $table->integer('estimated_minutes')->nullable()->after('estimated_delivery_at');
            $table->timestamp('courier_assigned_at')->nullable()->after('estimated_minutes');
            $table->timestamp('on_way_at')->nullable()->after('picked_up_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'estimated_minutes',
                'courier_assigned_at',
                'on_way_at',
            ]);
        });
    }
};
