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
        Schema::table('couriers', function (Blueprint $table) {
            // Platform ve Çalışma Bilgileri
            $table->enum('platform', ['android', 'ios'])->nullable()->after('phone');
            $table->enum('work_type', ['full_time', 'part_time', 'freelance'])->nullable()->after('platform');
            $table->enum('tier', ['bronze', 'silver', 'gold', 'platinum'])->default('bronze')->after('work_type');

            // Vergi Bilgileri
            $table->decimal('vat_rate', 5, 2)->default(0)->after('tier'); // KDV Oranı
            $table->decimal('withholding_rate', 5, 2)->default(0)->after('vat_rate'); // Tevkifat Oranı

            // Şirket Bilgileri
            $table->string('company_name')->nullable()->after('withholding_rate');
            $table->string('tax_office')->nullable()->after('company_name'); // Vergi Dairesi
            $table->string('tax_number')->nullable()->after('tax_office'); // Vergi Numarası
            $table->text('company_address')->nullable()->after('tax_number');

            // Ödeme Bilgileri
            $table->string('iban')->nullable()->after('company_address');
            $table->string('kobi_key')->nullable()->after('iban'); // Hesap Kobi Key

            // Ayarlar
            $table->boolean('can_reject_package')->default(true)->after('kobi_key'); // Paket Reddedebilir
            $table->integer('max_package_limit')->default(5)->after('can_reject_package'); // Paket taşıma limiti

            // Durum Kontrolü
            $table->boolean('payment_editing_enabled')->default(true)->after('max_package_limit'); // Ödeme Düzenleme
            $table->boolean('status_change_enabled')->default(true)->after('payment_editing_enabled'); // Durum Değiştirme

            // Çalışma Şekli ve Fiyatlandırma
            $table->enum('working_type', [
                'per_package',      // Paket Başı
                'per_km',           // Kilometre Başı
                'km_range',         // Kilometre Aralığı
                'package_plus_km',  // Paket Başı + Km Başı
                'fixed_km_plus_km', // Belirli Km + Km Başı
                'commission',       // Komisyon Oranı
                'tiered_package'    // Kademeli Paket Başı
            ])->default('per_package')->after('status_change_enabled');
            $table->json('pricing_data')->nullable()->after('working_type'); // 5 fiyatlandırma sekmesi için JSON
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('couriers', function (Blueprint $table) {
            $table->dropColumn([
                'platform',
                'work_type',
                'tier',
                'vat_rate',
                'withholding_rate',
                'company_name',
                'tax_office',
                'tax_number',
                'company_address',
                'iban',
                'kobi_key',
                'can_reject_package',
                'max_package_limit',
                'payment_editing_enabled',
                'status_change_enabled',
                'working_type',
                'pricing_data',
            ]);
        });
    }
};
