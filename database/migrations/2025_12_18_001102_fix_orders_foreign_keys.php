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
        // Drop the index first to avoid name collision
        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropUnique('orders_order_number_unique');
            });
        } catch (\Exception $e) {
            // Ignore if index doesn't exist (e.g. if partial run messed up something)
        }

        Schema::rename('orders', 'orders_old_fk');

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('courier_id')->nullable()->constrained('couriers')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('restaurant_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->text('customer_address');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            
            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            
            $table->string('payment_method')->default('cash');
            $table->boolean('is_paid')->default(false);
            
            $table->enum('status', ['pending', 'preparing', 'ready', 'on_delivery', 'delivered', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('estimated_delivery_at')->nullable();
            
            $table->integer('delivery_distance')->nullable();
            $table->text('cancel_reason')->nullable();
            
            $table->timestamps();
        });

        // Copy data
        $columns = [
            'id', 'order_number', 'user_id', 'customer_id', 'courier_id', 'branch_id', 'restaurant_id',
            'customer_name', 'customer_phone', 'customer_address', 'lat', 'lng', 
            'subtotal', 'delivery_fee', 'total', 'payment_method', 'is_paid', 'status', 'notes',
            'accepted_at', 'prepared_at', 'picked_up_at', 'delivered_at', 'cancelled_at', 'estimated_delivery_at',
            'delivery_distance', 'cancel_reason', 'created_at', 'updated_at'
        ];

        $colString = implode(', ', $columns);
        
        // We select matching columns from old table. 
        // Note: The order in SELECT must match the order in INSERT ($columns).
        // Since we select by name, it works.
        DB::statement("INSERT INTO orders ($colString) SELECT $colString FROM orders_old_fk");

        Schema::drop('orders_old_fk');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We can't easily reverse this without losing the fix, but let's try to restore structure roughly
        // Ideally we don't reverse a fix migration.
    }
};
