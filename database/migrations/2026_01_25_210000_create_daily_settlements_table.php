<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Günlük gelir dağılımı hesaplamalarını saklar.
     * Her gün, her branch ve restaurant kombinasyonu için bir kayıt oluşturulur.
     */
    public function up(): void
    {
        Schema::create('daily_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurant_connection_id')->nullable()->constrained()->nullOnDelete();
            $table->date('settlement_date');

            // Sipariş istatistikleri
            $table->unsignedInteger('order_count')->default(0);

            // Gelir tutarları
            $table->decimal('total_revenue', 12, 2)->default(0);      // Toplam sipariş tutarı (subtotal)
            $table->decimal('delivery_fee_total', 10, 2)->default(0); // Toplam teslimat ücreti

            // Dağılım
            $table->decimal('restaurant_share', 12, 2)->default(0);   // Restorana ödenecek
            $table->decimal('branch_commission', 10, 2)->default(0);  // Bayi komisyonu
            $table->decimal('courier_earnings', 10, 2)->default(0);   // Kurye kazancı toplam
            $table->decimal('branch_delivery_share', 10, 2)->default(0); // Bayi teslimat payı

            // Kullanılan oranlar (kayıt için)
            $table->decimal('commission_rate_used', 5, 2)->default(0);    // Uygulanan komisyon oranı
            $table->decimal('courier_rate_used', 5, 2)->default(0);       // Uygulanan kurye oranı

            // Durum
            $table->enum('status', ['pending', 'approved', 'paid', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();

            // Hangi siparişler dahil edildi
            $table->json('order_ids');

            $table->timestamps();

            // Her branch + restaurant + tarih kombinasyonu benzersiz olmalı
            $table->unique(['branch_id', 'restaurant_connection_id', 'settlement_date'], 'unique_daily_settlement');

            // Sık kullanılan sorgular için index
            $table->index(['settlement_date', 'status']);
            $table->index(['branch_id', 'settlement_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_settlements');
    }
};
