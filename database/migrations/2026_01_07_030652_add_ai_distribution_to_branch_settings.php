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
            $table->boolean('pool_ai_distribution')->default(true)->after('pool_auto_assign');
            $table->json('ai_distribution_weights')->nullable()->after('pool_ai_distribution');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_settings', function (Blueprint $table) {
            $table->dropColumn(['pool_ai_distribution', 'ai_distribution_weights']);
        });
    }
};
