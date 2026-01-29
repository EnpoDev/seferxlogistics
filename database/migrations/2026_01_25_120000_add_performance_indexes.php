<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Performans iyileştirmesi için sık kullanılan sorgu kolonlarına index ekleniyor.
     */
    public function up(): void
    {
        // Orders tablosu index'leri
        $this->addIndexIfNotExists('orders', ['courier_id', 'status'], 'orders_courier_status_index');
        $this->addIndexIfNotExists('orders', ['status', 'created_at'], 'orders_status_created_index');
        $this->addIndexIfNotExists('orders', ['pool_entered_at'], 'orders_pool_entered_index');
        $this->addIndexIfNotExists('orders', ['customer_phone'], 'orders_customer_phone_index');
        $this->addIndexIfNotExists('orders', ['branch_id', 'status'], 'orders_branch_status_index');

        // Couriers tablosu index'leri
        $this->addIndexIfNotExists('couriers', ['status', 'active_orders_count'], 'couriers_status_active_orders_index');
        $this->addIndexIfNotExists('couriers', ['notification_enabled', 'status'], 'couriers_notification_status_index');
        $this->addIndexIfNotExists('couriers', ['is_app_enabled'], 'couriers_app_enabled_index');

        // Transactions tablosu index'leri
        $this->addIndexIfNotExists('transactions', ['user_id', 'created_at'], 'transactions_user_created_index');
        $this->addIndexIfNotExists('transactions', ['status'], 'transactions_status_index');

        // Subscriptions tablosu index'leri
        $this->addIndexIfNotExists('subscriptions', ['user_id', 'status'], 'subscriptions_user_status_index');
        $this->addIndexIfNotExists('subscriptions', ['ends_at'], 'subscriptions_ends_at_index');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists('orders', 'orders_courier_status_index');
        $this->dropIndexIfExists('orders', 'orders_status_created_index');
        $this->dropIndexIfExists('orders', 'orders_pool_entered_index');
        $this->dropIndexIfExists('orders', 'orders_customer_phone_index');
        $this->dropIndexIfExists('orders', 'orders_branch_status_index');

        $this->dropIndexIfExists('couriers', 'couriers_status_active_orders_index');
        $this->dropIndexIfExists('couriers', 'couriers_notification_status_index');
        $this->dropIndexIfExists('couriers', 'couriers_app_enabled_index');

        $this->dropIndexIfExists('transactions', 'transactions_user_created_index');
        $this->dropIndexIfExists('transactions', 'transactions_status_index');

        $this->dropIndexIfExists('subscriptions', 'subscriptions_user_status_index');
        $this->dropIndexIfExists('subscriptions', 'subscriptions_ends_at_index');
    }

    /**
     * Add index if it doesn't already exist
     */
    private function addIndexIfNotExists(string $table, array $columns, string $indexName): void
    {
        if (!$this->indexExists($table, $indexName)) {
            Schema::table($table, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        }
    }

    /**
     * Drop index if it exists
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }

    /**
     * Check if index exists on table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = DB::connection()->getDriverName();

        if ($connection === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }
            return false;
        }

        // MySQL
        if ($connection === 'mysql') {
            $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
            return count($indexes) > 0;
        }

        // PostgreSQL
        if ($connection === 'pgsql') {
            $result = DB::select("SELECT 1 FROM pg_indexes WHERE indexname = ?", [$indexName]);
            return count($result) > 0;
        }

        return false;
    }
};
