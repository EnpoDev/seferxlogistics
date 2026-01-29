<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sık sorgulanan kolonlara performans indexleri ekler.
     */
    public function up(): void
    {
        // Orders tablosu indexleri
        Schema::table('orders', function (Blueprint $table) {
            // courier_id zaten foreign key ile indexli olabilir, kontrol et
            if (!$this->hasIndex('orders', 'orders_courier_id_index')) {
                $table->index('courier_id', 'orders_courier_id_index');
            }

            // Status filtrelemesi için
            if (!$this->hasIndex('orders', 'orders_status_index')) {
                $table->index('status', 'orders_status_index');
            }

            // Tarih bazlı sorgulamalar için
            if (!$this->hasIndex('orders', 'orders_created_at_index')) {
                $table->index('created_at', 'orders_created_at_index');
            }
        });

        // Order items tablosu indexi
        Schema::table('order_items', function (Blueprint $table) {
            if (!$this->hasIndex('order_items', 'order_items_order_id_index')) {
                $table->index('order_id', 'order_items_order_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_courier_id_index');
            $table->dropIndex('orders_status_index');
            $table->dropIndex('orders_created_at_index');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_order_id_index');
        });
    }

    /**
     * Check if an index exists on a table
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);

        foreach ($indexes as $index) {
            if ($index['name'] === $indexName) {
                return true;
            }
        }

        return false;
    }
};
