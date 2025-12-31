<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Başlangıç',
                'slug' => 'baslangic',
                'description' => 'Küçük işletmeler için ideal başlangıç paketi',
                'price' => 199.00,
                'billing_period' => 'monthly',
                'features' => [
                    'Sınırsız Sipariş',
                    '2 Kullanıcı',
                    'Temel Raporlar',
                    'E-posta Desteği',
                ],
                'max_users' => 2,
                'max_orders' => -1, // Unlimited
                'max_branches' => 1,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Profesyonel',
                'slug' => 'profesyonel',
                'description' => 'Büyüyen işletmeler için gelişmiş özellikler',
                'price' => 399.00,
                'billing_period' => 'monthly',
                'features' => [
                    'Sınırsız Sipariş',
                    '5 Kullanıcı',
                    'Gelişmiş Raporlar',
                    'Öncelikli Destek',
                    'Platform Entegrasyonları',
                    'Kurye Takibi',
                ],
                'max_users' => 5,
                'max_orders' => -1,
                'max_branches' => 3,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Kurumsal',
                'slug' => 'kurumsal',
                'description' => 'Büyük işletmeler için tam kapsamlı çözüm',
                'price' => 799.00,
                'billing_period' => 'monthly',
                'features' => [
                    'Sınırsız Sipariş',
                    'Sınırsız Kullanıcı',
                    'Özel Raporlar',
                    '7/24 Öncelikli Destek',
                    'Tüm Entegrasyonlar',
                    'Gelişmiş Kurye Yönetimi',
                    'API Erişimi',
                    'Özel Eğitim',
                ],
                'max_users' => -1, // Unlimited
                'max_orders' => -1,
                'max_branches' => -1,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 3,
            ],
            // Yearly plans
            [
                'name' => 'Başlangıç Yıllık',
                'slug' => 'baslangic-yillik',
                'description' => 'Küçük işletmeler için ideal başlangıç paketi - 2 ay ücretsiz',
                'price' => 1990.00,
                'billing_period' => 'yearly',
                'features' => [
                    'Sınırsız Sipariş',
                    '2 Kullanıcı',
                    'Temel Raporlar',
                    'E-posta Desteği',
                ],
                'max_users' => 2,
                'max_orders' => -1,
                'max_branches' => 1,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 4,
            ],
            [
                'name' => 'Profesyonel Yıllık',
                'slug' => 'profesyonel-yillik',
                'description' => 'Büyüyen işletmeler için gelişmiş özellikler - 2 ay ücretsiz',
                'price' => 3990.00,
                'billing_period' => 'yearly',
                'features' => [
                    'Sınırsız Sipariş',
                    '5 Kullanıcı',
                    'Gelişmiş Raporlar',
                    'Öncelikli Destek',
                    'Platform Entegrasyonları',
                    'Kurye Takibi',
                ],
                'max_users' => 5,
                'max_orders' => -1,
                'max_branches' => 3,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Kurumsal Yıllık',
                'slug' => 'kurumsal-yillik',
                'description' => 'Büyük işletmeler için tam kapsamlı çözüm - 2 ay ücretsiz',
                'price' => 7990.00,
                'billing_period' => 'yearly',
                'features' => [
                    'Sınırsız Sipariş',
                    'Sınırsız Kullanıcı',
                    'Özel Raporlar',
                    '7/24 Öncelikli Destek',
                    'Tüm Entegrasyonlar',
                    'Gelişmiş Kurye Yönetimi',
                    'API Erişimi',
                    'Özel Eğitim',
                ],
                'max_users' => -1,
                'max_orders' => -1,
                'max_branches' => -1,
                'is_active' => true,
                'is_featured' => false,
                'sort_order' => 6,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}

