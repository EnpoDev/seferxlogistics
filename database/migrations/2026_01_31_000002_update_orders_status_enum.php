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
     * Bug fix: Order model'de STATUS_RETURNED ve STATUS_APPROVED tanimli
     * ancak DB enum'da bu degerler yoktu.
     */
    public function up(): void
    {
        // MySQL'de enum degistirmek icin raw SQL kullanmak gerekiyor
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'preparing', 'ready', 'on_delivery', 'delivered', 'cancelled', 'returned', 'approved') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Dikkat: Mevcut 'returned' veya 'approved' kayitlari varsa bu hata verir
        // Oncelikle bu kayitlari baska bir status'a cevirin
        DB::statement("UPDATE orders SET status = 'cancelled' WHERE status IN ('returned', 'approved')");
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'preparing', 'ready', 'on_delivery', 'delivered', 'cancelled') DEFAULT 'pending'");
    }
};
