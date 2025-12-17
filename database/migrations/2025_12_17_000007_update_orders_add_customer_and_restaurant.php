<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('restaurant_id')->nullable()->after('branch_id')->constrained()->nullOnDelete();
            $table->string('payment_method')->default('cash')->after('total'); // cash, card, online
            $table->boolean('is_paid')->default(false)->after('payment_method');
            $table->timestamp('estimated_delivery_at')->nullable()->after('cancelled_at');
            $table->integer('delivery_distance')->nullable()->after('estimated_delivery_at'); // in meters
            $table->text('cancel_reason')->nullable()->after('delivery_distance');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['restaurant_id']);
            $table->dropColumn([
                'customer_id',
                'restaurant_id',
                'payment_method',
                'is_paid',
                'estimated_delivery_at',
                'delivery_distance',
                'cancel_reason'
            ]);
        });
    }
};

