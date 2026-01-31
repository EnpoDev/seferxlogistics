<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->index('status');
            $table->index('created_at');
            $table->index(['status', 'created_at']); // Composite index
        });

        // couriers tablosu - status ve user_id sik filtreleniyor
        Schema::table('couriers', function (Blueprint $table) {
            // user_id index'i yoksa ekle (FK varsa otomatik eklenmis olabilir)
            if (!$this->hasIndex('couriers', 'couriers_user_id_index')) {
                $table->index('user_id');
            }
            $table->index('status');
        });

        // branches tablosu - user_id sik filtreleniyor
        Schema::table('branches', function (Blueprint $table) {
            if (!$this->hasIndex('branches', 'branches_user_id_index')) {
                $table->index('user_id');
            }
        });

        // subscriptions tablosu - status ve expires_at sik filtreleniyor
        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (Schema::hasColumn('subscriptions', 'status')) {
                    $table->index('status');
                }
                if (Schema::hasColumn('subscriptions', 'expires_at')) {
                    $table->index('expires_at');
                }
                if (Schema::hasColumn('subscriptions', 'payment_card_id')) {
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
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['status', 'created_at']);
        });

        Schema::table('couriers', function (Blueprint $table) {
            if ($this->hasIndex('couriers', 'couriers_user_id_index')) {
                $table->dropIndex(['user_id']);
            }
            $table->dropIndex(['status']);
        });

        Schema::table('branches', function (Blueprint $table) {
            if ($this->hasIndex('branches', 'branches_user_id_index')) {
                $table->dropIndex(['user_id']);
            }
        });

        if (Schema::hasTable('subscriptions')) {
            Schema::table('subscriptions', function (Blueprint $table) {
                if (Schema::hasColumn('subscriptions', 'status')) {
                    $table->dropIndex(['status']);
                }
                if (Schema::hasColumn('subscriptions', 'expires_at')) {
                    $table->dropIndex(['expires_at']);
                }
                if (Schema::hasColumn('subscriptions', 'payment_card_id')) {
                    $table->dropIndex(['payment_card_id']);
                }
            });
        }
    }

    /**
     * Check if an index exists
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
