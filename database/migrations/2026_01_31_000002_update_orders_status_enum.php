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
     *
     * NOT: SQLite ENUM desteklemedigi icin veritabani tipine gore farkli islem yapiyoruz
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            // MySQL/MariaDB icin ENUM guncelle
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'preparing', 'ready', 'on_delivery', 'delivered', 'cancelled', 'returned', 'approved') DEFAULT 'pending'");
        } else {
            // SQLite ve diger veritabanlari icin: status zaten string olarak saklandigi icin
            // enum kisitlamasi uygulama katmaninda yapiliyor, migration'da bir sey yapmamiza gerek yok
            // Laravel SQLite'ta ENUM'lari string olarak saklar
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            // Dikkat: Mevcut 'returned' veya 'approved' kayitlari varsa bu hata verir
            // Oncelikle bu kayitlari baska bir status'a cevirin
            DB::statement("UPDATE orders SET status = 'cancelled' WHERE status IN ('returned', 'approved')");
            DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'preparing', 'ready', 'on_delivery', 'delivered', 'cancelled') DEFAULT 'pending'");
        }
        // SQLite icin geri alma gerekmiyor
    }
};
