<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('restaurant_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
            $table->decimal('discounted_price', 10, 2)->nullable()->after('price');
            $table->json('options')->nullable()->after('description'); // Extra options like size, toppings
            $table->integer('preparation_time')->nullable()->after('in_stock'); // in minutes
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['restaurant_id']);
            $table->dropColumn(['restaurant_id', 'discounted_price', 'options', 'preparation_time']);
        });
    }
};

