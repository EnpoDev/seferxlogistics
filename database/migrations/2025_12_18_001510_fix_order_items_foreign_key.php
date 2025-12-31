<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('order_items', 'order_items_old_fk');

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('product_name');
            $table->decimal('price', 10, 2);
            $table->integer('quantity');
            $table->decimal('total', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Copy data
        $columns = [
            'id', 'order_id', 'product_id', 'product_name', 'price', 
            'quantity', 'total', 'notes', 'created_at', 'updated_at'
        ];

        $colString = implode(', ', $columns);
        
        DB::statement("INSERT INTO order_items ($colString) SELECT $colString FROM order_items_old_fk");

        Schema::drop('order_items_old_fk');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 
    }
};
