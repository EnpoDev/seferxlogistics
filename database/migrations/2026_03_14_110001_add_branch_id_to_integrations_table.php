<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->nullOnDelete();

            // Eski unique('platform') kaldirip branch_id ile birlikte yeni unique ekle
            // Her branch ayni platform'a ayri entegrasyon yapabilmeli
            $table->dropUnique(['platform']);
            $table->unique(['platform', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->dropUnique(['platform', 'branch_id']);
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
            $table->unique('platform');
        });
    }
};
