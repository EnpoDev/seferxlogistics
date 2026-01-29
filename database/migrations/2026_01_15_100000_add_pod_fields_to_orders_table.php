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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('pod_photo_path')->nullable()->after('cancel_reason');
            $table->timestamp('pod_timestamp')->nullable()->after('pod_photo_path');
            $table->json('pod_location')->nullable()->after('pod_timestamp');
            $table->string('pod_note', 500)->nullable()->after('pod_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['pod_photo_path', 'pod_timestamp', 'pod_location', 'pod_note']);
        });
    }
};
