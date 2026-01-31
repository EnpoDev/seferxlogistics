<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bug fix: current_order_id FK constraint eksikti
     *
     * NOT: SQLite mevcut tablolara FK eklenmeyi tam desteklemez
     * Bu nedenle veritabani tipine gore farkli islem yapiyoruz
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            Schema::table('couriers', function (Blueprint $table) {
                // current_order_id icin FK constraint ekle (nullable)
                $table->foreign('current_order_id')
                    ->references('id')
                    ->on('orders')
                    ->nullOnDelete();

                // current_order_id icin index ekle
                $table->index('current_order_id');
            });
        } else {
            // SQLite icin sadece index ekle (FK kisitlamasi uygulama katmaninda)
            Schema::table('couriers', function (Blueprint $table) {
                $table->index('current_order_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            Schema::table('couriers', function (Blueprint $table) {
                $table->dropForeign(['current_order_id']);
                $table->dropIndex(['current_order_id']);
            });
        } else {
            Schema::table('couriers', function (Blueprint $table) {
                $table->dropIndex(['current_order_id']);
            });
        }
    }
};
