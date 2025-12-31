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
        Schema::table('couriers', function (Blueprint $table) {
            $table->foreignId('pricing_policy_id')
                ->nullable()
                ->after('is_app_enabled')
                ->constrained('pricing_policies')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->dropForeign(['pricing_policy_id']);
            $table->dropColumn('pricing_policy_id');
        });
    }
};
