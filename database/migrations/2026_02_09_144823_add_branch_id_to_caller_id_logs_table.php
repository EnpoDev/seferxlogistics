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
        // Add branch_id column
        if (!Schema::hasColumn('caller_id_logs', 'branch_id')) {
            Schema::table('caller_id_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable();
            });
        }

        // Copy data from restaurant_id to branch_id (if any exists)
        if (Schema::hasColumn('caller_id_logs', 'restaurant_id')) {
            \DB::table('caller_id_logs')->whereNotNull('restaurant_id')->update([
                'branch_id' => \DB::raw('restaurant_id'),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('caller_id_logs', 'branch_id')) {
            Schema::table('caller_id_logs', function (Blueprint $table) {
                $table->dropColumn('branch_id');
            });
        }
    }
};
