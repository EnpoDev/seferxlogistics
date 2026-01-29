<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Varsayilan planlari olustur - Bayi kaydi icin gerekli
     */
    public function up(): void
    {
        // Plan yoksa varsayilan planlari olustur
        if (DB::table('plans')->count() === 0) {
            DB::table('plans')->insert([
                [
                    'name' => 'Baslangic',
                    'slug' => 'baslangic',
                    'description' => 'Kucuk isletmeler icin ideal baslangic paketi',
                    'price' => 0,
                    'billing_period' => 'monthly',
                    'features' => json_encode([
                        'Sinirsiz siparis',
                        'Temel raporlama',
                        'E-posta destegi',
                    ]),
                    'max_users' => 2,
                    'max_orders' => null,
                    'max_branches' => 1,
                    'is_active' => true,
                    'is_featured' => false,
                    'sort_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Profesyonel',
                    'slug' => 'profesyonel',
                    'description' => 'Buyuyen isletmeler icin profesyonel cozum',
                    'price' => 499,
                    'billing_period' => 'monthly',
                    'features' => json_encode([
                        'Sinirsiz siparis',
                        'Gelismis raporlama',
                        'Oncelikli destek',
                        'API erisimi',
                        'Coklu sube',
                    ]),
                    'max_users' => 10,
                    'max_orders' => null,
                    'max_branches' => 5,
                    'is_active' => true,
                    'is_featured' => true,
                    'sort_order' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Kurumsal',
                    'slug' => 'kurumsal',
                    'description' => 'Buyuk firmalar icin tam ozellikli kurumsal paket',
                    'price' => 999,
                    'billing_period' => 'monthly',
                    'features' => json_encode([
                        'Sinirsiz siparis',
                        'Sinirsiz kullanici',
                        'Sinirsiz sube',
                        'Ozel destek',
                        'API erisimi',
                        'Beyaz etiket',
                        'Ozel entegrasyonlar',
                    ]),
                    'max_users' => null,
                    'max_orders' => null,
                    'max_branches' => null,
                    'is_active' => true,
                    'is_featured' => false,
                    'sort_order' => 3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Geri alma - varsayilan planlari sil
        DB::table('plans')->whereIn('slug', ['baslangic', 'profesyonel', 'kurumsal'])->delete();
    }
};
