<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disable foreign key checks for SQLite
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=off;');
        }

        Schema::table('couriers', function (Blueprint $table) {
            if (!Schema::hasColumn('couriers', 'password')) {
                $table->string('password')->nullable();
            }
            if (!Schema::hasColumn('couriers', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }
            if (!Schema::hasColumn('couriers', 'device_token')) {
                $table->string('device_token')->nullable();
            }
            if (!Schema::hasColumn('couriers', 'is_app_enabled')) {
                $table->boolean('is_app_enabled')->default(true);
            }
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys=on;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->dropColumn(['password', 'last_login_at', 'device_token', 'is_app_enabled']);
        });
    }
};

