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
        // SQLite doesn't support modifying enum columns directly or dropping constraints easily
        // We need to recreate the table with the new check constraint
        
        // 1. Rename old table
        Schema::rename('couriers', 'couriers_old');

        // 2. Create new table with updated enum
        Schema::create('couriers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            // New status values: available, delivering, offline, active
            $table->enum('status', ['available', 'delivering', 'offline', 'active'])->default('available');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedBigInteger('current_order_id')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('tc_no')->nullable();
            $table->string('vehicle_plate')->nullable();
            $table->timestamps();
        });

        // 3. Copy data from old table to new table
        // Map old status values to new ones if necessary
        // active -> active
        // inactive -> offline
        // busy -> delivering (or keep as is if mapping is not strictly required but values must be valid)
        
        $oldCouriers = DB::table('couriers_old')->get();
        
        foreach ($oldCouriers as $courier) {
            $newStatus = match($courier->status) {
                'inactive' => 'offline',
                'busy' => 'delivering',
                default => 'active' // Keep 'active' as is, default others to 'active' or 'available'
            };

            // If the status is already one of the new valid ones (e.g. if data was somehow forced), keep it
            if (in_array($courier->status, ['available', 'delivering', 'offline', 'active'])) {
                $newStatus = $courier->status;
            }

            DB::table('couriers')->insert([
                'id' => $courier->id,
                'name' => $courier->name,
                'phone' => $courier->phone,
                'email' => $courier->email,
                'status' => $newStatus,
                'lat' => $courier->lat,
                'lng' => $courier->lng,
                'current_order_id' => $courier->current_order_id,
                'photo_path' => $courier->photo_path,
                'tc_no' => $courier->tc_no,
                'vehicle_plate' => $courier->vehicle_plate,
                'created_at' => $courier->created_at,
                'updated_at' => $courier->updated_at,
            ]);
        }

        // 4. Drop old table
        Schema::drop('couriers_old');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to old schema
        Schema::rename('couriers', 'couriers_new');

        Schema::create('couriers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->enum('status', ['active', 'inactive', 'busy'])->default('active');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedBigInteger('current_order_id')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('tc_no')->nullable();
            $table->string('vehicle_plate')->nullable();
            $table->timestamps();
        });

        $newCouriers = DB::table('couriers_new')->get();
        
        foreach ($newCouriers as $courier) {
            // Map new status back to old
            $oldStatus = match($courier->status) {
                'offline' => 'inactive',
                'delivering' => 'busy',
                'available' => 'active',
                default => 'active'
            };

            DB::table('couriers')->insert([
                'id' => $courier->id,
                'name' => $courier->name,
                'phone' => $courier->phone,
                'email' => $courier->email,
                'status' => $oldStatus,
                'lat' => $courier->lat,
                'lng' => $courier->lng,
                'current_order_id' => $courier->current_order_id,
                'photo_path' => $courier->photo_path,
                'tc_no' => $courier->tc_no,
                'vehicle_plate' => $courier->vehicle_plate,
                'created_at' => $courier->created_at,
                'updated_at' => $courier->updated_at,
            ]);
        }

        Schema::drop('couriers_new');
    }
};
