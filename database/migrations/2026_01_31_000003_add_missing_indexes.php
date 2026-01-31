<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Performans iyilestirmesi: Sik kullanilan sorgular icin index ekle
     */
    public function up(): void
    {
        // orders tablosu - status ve created_at sik filtreleniyor
        Schema::table('orders', function (Blueprint $table) {
            if (!$this->indexExists('orders', 'orders_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('orders', 'orders_created_at_index')) {
                $table->index('created_at');
            }
            if (!$this->indexExists('orders', 'orders_status_created_at_index')) {
                $table->index(['status', 'created_at']);
            }
        });

        // couriers tablosu - status ve user_id sik filtreleniyor
        Schema::table('couriers', function (Blueprint $table) {
            if (!$this->indexExists('couriers', 'couriers_user_id_index')) {
                $table->index('user_id');
            }
            if (!$this->indexExists('couriers', 'couriers_status_index')) {
                $table->index('status');
            }
        });

        // branches tablosu - user_id sik filtreleniyor
        Schema::table('branches', function (Blueprint $table) {
            if (!$this->indexExists('branches', 'branches_user_id_index')) {
                $table->index('user_id');
            }
        });

        // subscriptions tablosu - status ve expires_at sik filtreleniyor
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (Schema::hasColumn('subscriptions', 'status') && !$this->indexExists('subscriptions', 'subscriptions_status_index')) {
                    $table->index('status');
                }
                if (Schema::hasColumn('subscriptions', 'ends_at') && !$this->indexExists('subscriptions', 'subscriptions_ends_at_index')) {
                    $table->index('ends_at');
                }
                if (Schema::hasColumn('subscriptions', 'payment_card_id') && !$this->indexExists('subscriptions', 'subscriptions_payment_card_id_index')) {
                    $table->index('payment_card_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if ($this->indexExists('orders', 'orders_status_index')) {
                $table->dropIndex(['status']);
            }
            if ($this->indexExists('orders', 'orders_created_at_index')) {
                $table->dropIndex(['created_at']);
            }
            if ($this->indexExists('orders', 'orders_status_created_at_index')) {
                $table->dropIndex(['status', 'created_at']);
            }
        });

        Schema::table('couriers', function (Blueprint $table) {
            if ($this->indexExists('couriers', 'couriers_user_id_index')) {
                $table->dropIndex(['user_id']);
            }
            if ($this->indexExists('couriers', 'couriers_status_index')) {
                $table->dropIndex(['status']);
            }
        });

        Schema::table('branches', function (Blueprint $table) {
            if ($this->indexExists('branches', 'branches_user_id_index')) {
                $table->dropIndex(['user_id']);
            }
        });

        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if ($this->indexExists('subscriptions', 'subscriptions_status_index')) {
                    $table->dropIndex(['status']);
                }
                if ($this->indexExists('subscriptions', 'subscriptions_ends_at_index')) {
                    $table->dropIndex(['ends_at']);
                }
                if ($this->indexExists('subscriptions', 'subscriptions_payment_card_id_index')) {
                    $table->dropIndex(['payment_card_id']);
                }
            });
        }
    }

    /**
     * Check if an index exists (cross-database compatible)
     */
    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'sqlite') {
                // SQLite icin pragma kullan
                $indexes = DB::select("PRAGMA index_list('{$table}')");
                foreach ($indexes as $index) {
                    if ($index->name === $indexName) {
                        return true;
                    }
                }
                return false;
            }

            // MySQL/MariaDB/PostgreSQL icin Schema::getIndexes kullan
            $indexes = Schema::getIndexes($table);
            foreach ($indexes as $index) {
                if ($index['name'] === $indexName) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            // Hata durumunda index yokmus gibi davran
            return false;
        }
    }
};
