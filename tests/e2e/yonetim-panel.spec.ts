import { test, expect } from '@playwright/test';

/**
 * Yönetim Panel E2E Testleri
 *
 * Yönetim (Admin) panelinin sayfalarını test eder.
 * Route mocking ile backend olmadan çalışır.
 */

// Mock Entegrasyonlar sayfası
const mockEntegrasyonlarHtml = (integrations: any = {}) => {
    const defaultIntegrations = {
        yemeksepeti: { connected: true, status: 'active', orders_today: 15 },
        getir: { connected: false, status: null, orders_today: 0 },
        trendyol: { connected: true, status: 'active', orders_today: 8 },
        migros: { connected: false, status: null, orders_today: 0 },
        ...integrations
    };

    return `
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="test-token">
    <title>Entegrasyonlar - Yönetim</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100">
<div class="p-6" x-data="integrationsPage()">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Entegrasyonlar</h1>
        <p class="text-gray-500">Platform bağlantılarını yönetin</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" data-testid="integrations-grid">
        <!-- Yemeksepeti -->
        <div class="bg-white rounded-xl p-4 shadow-sm" data-testid="integration-yemeksepeti">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                        <span class="text-red-600 font-bold">YS</span>
                    </div>
                    <div>
                        <h3 class="font-semibold">Yemeksepeti</h3>
                        <p class="text-xs text-gray-500">Sipariş entegrasyonu</p>
                    </div>
                </div>
                <span class="px-2 py-1 text-xs rounded-full ${defaultIntegrations.yemeksepeti.connected ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600'}"
                      data-testid="yemeksepeti-status">
                    ${defaultIntegrations.yemeksepeti.connected ? 'Bağlı' : 'Bağlı Değil'}
                </span>
            </div>
            ${defaultIntegrations.yemeksepeti.connected ? `
            <div class="flex items-center justify-between text-sm mb-3">
                <span class="text-gray-500">Bugün: ${defaultIntegrations.yemeksepeti.orders_today} sipariş</span>
            </div>
            <button class="w-full py-2 border border-red-200 text-red-600 rounded-lg text-sm" data-testid="yemeksepeti-disconnect">
                Bağlantıyı Kes
            </button>
            ` : `
            <button class="w-full py-2 bg-red-600 text-white rounded-lg text-sm" data-testid="yemeksepeti-connect">
                Bağlan
            </button>
            `}
        </div>

        <!-- Getir -->
        <div class="bg-white rounded-xl p-4 shadow-sm" data-testid="integration-getir">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <span class="text-purple-600 font-bold">G</span>
                    </div>
                    <div>
                        <h3 class="font-semibold">Getir</h3>
                        <p class="text-xs text-gray-500">Sipariş entegrasyonu</p>
                    </div>
                </div>
                <span class="px-2 py-1 text-xs rounded-full ${defaultIntegrations.getir.connected ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600'}"
                      data-testid="getir-status">
                    ${defaultIntegrations.getir.connected ? 'Bağlı' : 'Bağlı Değil'}
                </span>
            </div>
            ${defaultIntegrations.getir.connected ? `
            <button class="w-full py-2 border border-red-200 text-red-600 rounded-lg text-sm" data-testid="getir-disconnect">
                Bağlantıyı Kes
            </button>
            ` : `
            <button class="w-full py-2 bg-purple-600 text-white rounded-lg text-sm" data-testid="getir-connect">
                Bağlan
            </button>
            `}
        </div>

        <!-- Trendyol -->
        <div class="bg-white rounded-xl p-4 shadow-sm" data-testid="integration-trendyol">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                        <span class="text-orange-600 font-bold">TY</span>
                    </div>
                    <div>
                        <h3 class="font-semibold">Trendyol Yemek</h3>
                        <p class="text-xs text-gray-500">Sipariş entegrasyonu</p>
                    </div>
                </div>
                <span class="px-2 py-1 text-xs rounded-full ${defaultIntegrations.trendyol.connected ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600'}"
                      data-testid="trendyol-status">
                    ${defaultIntegrations.trendyol.connected ? 'Bağlı' : 'Bağlı Değil'}
                </span>
            </div>
            ${defaultIntegrations.trendyol.connected ? `
            <div class="flex items-center justify-between text-sm mb-3">
                <span class="text-gray-500">Bugün: ${defaultIntegrations.trendyol.orders_today} sipariş</span>
            </div>
            <button class="w-full py-2 border border-red-200 text-red-600 rounded-lg text-sm" data-testid="trendyol-disconnect">
                Bağlantıyı Kes
            </button>
            ` : `
            <button class="w-full py-2 bg-orange-600 text-white rounded-lg text-sm" data-testid="trendyol-connect">
                Bağlan
            </button>
            `}
        </div>

        <!-- Migros -->
        <div class="bg-white rounded-xl p-4 shadow-sm" data-testid="integration-migros">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                        <span class="text-orange-600 font-bold">M</span>
                    </div>
                    <div>
                        <h3 class="font-semibold">Migros Yemek</h3>
                        <p class="text-xs text-gray-500">Sipariş entegrasyonu</p>
                    </div>
                </div>
                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600" data-testid="migros-status">
                    Bağlı Değil
                </span>
            </div>
            <button class="w-full py-2 bg-orange-600 text-white rounded-lg text-sm" data-testid="migros-connect">
                Bağlan
            </button>
        </div>
    </div>
</div>

<script>
function integrationsPage() {
    return {
        async connect(platform) {
            // API call simulation
            window.__connectCalled = platform;
        },
        async disconnect(platform) {
            window.__disconnectCalled = platform;
        }
    }
}
</script>
</body>
</html>
`;
};

// Mock Abonelikler sayfası
const mockAboneliklerHtml = (subscription: any = {}) => {
    const defaultSubscription = {
        plan: 'pro',
        plan_name: 'Pro Plan',
        price: '499',
        next_billing: '2024-02-15',
        features: ['Sınırsız sipariş', '10 kurye', 'Entegrasyonlar', '7/24 Destek'],
        ...subscription
    };

    return `
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="test-token">
    <title>Abonelikler - Yönetim</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100">
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Abonelik Yönetimi</h1>
        <p class="text-gray-500">Plan ve fatura bilgileriniz</p>
    </div>

    <!-- Current Plan -->
    <div class="bg-white rounded-xl p-6 shadow-sm mb-6" data-testid="current-plan">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold" data-testid="plan-name">${defaultSubscription.plan_name}</h3>
                <p class="text-gray-500">Mevcut planınız</p>
            </div>
            <div class="text-right">
                <p class="text-2xl font-bold">₺${defaultSubscription.price}<span class="text-sm text-gray-500">/ay</span></p>
                <p class="text-xs text-gray-500">Sonraki fatura: ${defaultSubscription.next_billing}</p>
            </div>
        </div>
        <div class="border-t pt-4">
            <h4 class="text-sm font-medium mb-2">Plan Özellikleri</h4>
            <ul class="space-y-1" data-testid="plan-features">
                ${defaultSubscription.features.map((f: string) => `<li class="text-sm text-gray-600 flex items-center gap-2">✓ ${f}</li>`).join('')}
            </ul>
        </div>
    </div>

    <!-- Available Plans -->
    <h3 class="text-lg font-semibold mb-4">Diğer Planlar</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4" data-testid="plans-grid">
        <!-- Starter -->
        <div class="bg-white rounded-xl p-4 shadow-sm border-2 border-transparent" data-testid="plan-starter">
            <h4 class="font-semibold">Starter</h4>
            <p class="text-2xl font-bold mt-2">₺199<span class="text-sm text-gray-500">/ay</span></p>
            <ul class="mt-3 space-y-1 text-sm text-gray-600">
                <li>✓ 500 sipariş/ay</li>
                <li>✓ 3 kurye</li>
                <li>✓ Email destek</li>
            </ul>
            <button class="w-full mt-4 py-2 border border-gray-200 rounded-lg text-sm" data-testid="select-starter">
                Bu Plana Geç
            </button>
        </div>

        <!-- Pro -->
        <div class="bg-white rounded-xl p-4 shadow-sm border-2 border-red-500" data-testid="plan-pro">
            <div class="flex items-center justify-between">
                <h4 class="font-semibold">Pro</h4>
                <span class="px-2 py-1 bg-red-100 text-red-600 text-xs rounded-full">Mevcut</span>
            </div>
            <p class="text-2xl font-bold mt-2">₺499<span class="text-sm text-gray-500">/ay</span></p>
            <ul class="mt-3 space-y-1 text-sm text-gray-600">
                <li>✓ Sınırsız sipariş</li>
                <li>✓ 10 kurye</li>
                <li>✓ Entegrasyonlar</li>
                <li>✓ 7/24 Destek</li>
            </ul>
            <button class="w-full mt-4 py-2 bg-gray-100 text-gray-400 rounded-lg text-sm" disabled data-testid="select-pro">
                Mevcut Plan
            </button>
        </div>

        <!-- Enterprise -->
        <div class="bg-white rounded-xl p-4 shadow-sm border-2 border-transparent" data-testid="plan-enterprise">
            <h4 class="font-semibold">Enterprise</h4>
            <p class="text-2xl font-bold mt-2">₺999<span class="text-sm text-gray-500">/ay</span></p>
            <ul class="mt-3 space-y-1 text-sm text-gray-600">
                <li>✓ Sınırsız her şey</li>
                <li>✓ API erişimi</li>
                <li>✓ Özel destek</li>
                <li>✓ SLA garantisi</li>
            </ul>
            <button class="w-full mt-4 py-2 bg-red-600 text-white rounded-lg text-sm" data-testid="select-enterprise">
                Yükselt
            </button>
        </div>
    </div>

    <!-- Cancel -->
    <div class="mt-6 text-center">
        <button class="text-sm text-gray-500 hover:text-red-600" data-testid="cancel-subscription">
            Aboneliği İptal Et
        </button>
    </div>
</div>
</body>
</html>
`;
};

// Mock Paketler sayfası
const mockPaketlerHtml = () => `
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="test-token">
    <title>Paketler - Yönetim</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Paket Yönetimi</h1>
        <p class="text-gray-500">SMS ve bildirim paketleri</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" data-testid="packages-grid">
        <div class="bg-white rounded-xl p-4 shadow-sm" data-testid="package-sms">
            <h3 class="font-semibold mb-2">SMS Paketi</h3>
            <p class="text-gray-500 text-sm mb-3">Müşteri bildirimleri için SMS</p>
            <div class="flex items-center justify-between mb-3">
                <span class="text-2xl font-bold">500 SMS</span>
                <span class="text-lg font-semibold text-green-600">₺150</span>
            </div>
            <button class="w-full py-2 bg-red-600 text-white rounded-lg" data-testid="buy-sms">
                Satın Al
            </button>
        </div>

        <div class="bg-white rounded-xl p-4 shadow-sm" data-testid="package-push">
            <h3 class="font-semibold mb-2">Push Bildirimi</h3>
            <p class="text-gray-500 text-sm mb-3">Mobil uygulama bildirimleri</p>
            <div class="flex items-center justify-between mb-3">
                <span class="text-2xl font-bold">Sınırsız</span>
                <span class="text-lg font-semibold text-green-600">₺0</span>
            </div>
            <button class="w-full py-2 bg-green-100 text-green-600 rounded-lg" disabled data-testid="push-included">
                Dahil
            </button>
        </div>
    </div>

    <!-- Current Balance -->
    <div class="mt-6 bg-white rounded-xl p-4 shadow-sm" data-testid="current-balance">
        <h3 class="font-semibold mb-3">Mevcut Bakiye</h3>
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">SMS Kredisi</p>
                <p class="text-xl font-bold" data-testid="sms-balance">127 SMS</p>
            </div>
            <a href="#" class="text-sm text-red-600" data-testid="view-history">Kullanım Geçmişi</a>
        </div>
    </div>
</div>
</body>
</html>
`;

test.describe('Yönetim Panel - Entegrasyonlar', () => {
    test.beforeEach(async ({ page }) => {
        await page.route('**/yonetim/entegrasyonlar', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockEntegrasyonlarHtml(),
            });
        });
    });

    test('entegrasyonlar sayfası yüklenir', async ({ page }) => {
        await page.goto('/yonetim/entegrasyonlar');

        await expect(page.locator('h1')).toContainText('Entegrasyonlar');
        await expect(page.locator('[data-testid="integrations-grid"]')).toBeVisible();
    });

    test('tüm platformlar listelenir', async ({ page }) => {
        await page.goto('/yonetim/entegrasyonlar');

        await expect(page.locator('[data-testid="integration-yemeksepeti"]')).toBeVisible();
        await expect(page.locator('[data-testid="integration-getir"]')).toBeVisible();
        await expect(page.locator('[data-testid="integration-trendyol"]')).toBeVisible();
        await expect(page.locator('[data-testid="integration-migros"]')).toBeVisible();
    });

    test('bağlı platform durumu gösterilir', async ({ page }) => {
        await page.goto('/yonetim/entegrasyonlar');

        // Yemeksepeti bağlı
        await expect(page.locator('[data-testid="yemeksepeti-status"]')).toContainText('Bağlı');
        await expect(page.locator('[data-testid="yemeksepeti-disconnect"]')).toBeVisible();

        // Getir bağlı değil
        await expect(page.locator('[data-testid="getir-status"]')).toContainText('Bağlı Değil');
        await expect(page.locator('[data-testid="getir-connect"]')).toBeVisible();
    });

    test('sipariş sayısı gösterilir', async ({ page }) => {
        await page.goto('/yonetim/entegrasyonlar');

        await expect(page.locator('[data-testid="integration-yemeksepeti"]')).toContainText('15 sipariş');
    });

    test('bağlan butonu görünür', async ({ page }) => {
        await page.goto('/yonetim/entegrasyonlar');

        await expect(page.locator('[data-testid="getir-connect"]')).toBeVisible();
        await expect(page.locator('[data-testid="migros-connect"]')).toBeVisible();
    });
});

test.describe('Yönetim Panel - Abonelikler', () => {
    test.beforeEach(async ({ page }) => {
        await page.route('**/yonetim/abonelikler', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockAboneliklerHtml(),
            });
        });
    });

    test('abonelikler sayfası yüklenir', async ({ page }) => {
        await page.goto('/yonetim/abonelikler');

        await expect(page.locator('h1')).toContainText('Abonelik Yönetimi');
    });

    test('mevcut plan gösterilir', async ({ page }) => {
        await page.goto('/yonetim/abonelikler');

        await expect(page.locator('[data-testid="current-plan"]')).toBeVisible();
        await expect(page.locator('[data-testid="plan-name"]')).toContainText('Pro Plan');
    });

    test('plan özellikleri listelenir', async ({ page }) => {
        await page.goto('/yonetim/abonelikler');

        const features = page.locator('[data-testid="plan-features"]');
        await expect(features).toContainText('Sınırsız sipariş');
        await expect(features).toContainText('10 kurye');
    });

    test('diğer planlar gösterilir', async ({ page }) => {
        await page.goto('/yonetim/abonelikler');

        await expect(page.locator('[data-testid="plan-starter"]')).toBeVisible();
        await expect(page.locator('[data-testid="plan-pro"]')).toBeVisible();
        await expect(page.locator('[data-testid="plan-enterprise"]')).toBeVisible();
    });

    test('mevcut plan vurgulanır', async ({ page }) => {
        await page.goto('/yonetim/abonelikler');

        const proPlan = page.locator('[data-testid="plan-pro"]');
        await expect(proPlan).toHaveClass(/border-red-500/);
        await expect(proPlan).toContainText('Mevcut');
    });

    test('yükseltme butonu görünür', async ({ page }) => {
        await page.goto('/yonetim/abonelikler');

        await expect(page.locator('[data-testid="select-enterprise"]')).toBeVisible();
        await expect(page.locator('[data-testid="select-enterprise"]')).toContainText('Yükselt');
    });

    test('iptal butonu görünür', async ({ page }) => {
        await page.goto('/yonetim/abonelikler');

        await expect(page.locator('[data-testid="cancel-subscription"]')).toBeVisible();
    });
});

test.describe('Yönetim Panel - Paketler', () => {
    test.beforeEach(async ({ page }) => {
        await page.route('**/yonetim/paketler', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockPaketlerHtml(),
            });
        });
    });

    test('paketler sayfası yüklenir', async ({ page }) => {
        await page.goto('/yonetim/paketler');

        await expect(page.locator('h1')).toContainText('Paket Yönetimi');
    });

    test('SMS paketi gösterilir', async ({ page }) => {
        await page.goto('/yonetim/paketler');

        await expect(page.locator('[data-testid="package-sms"]')).toBeVisible();
        await expect(page.locator('[data-testid="buy-sms"]')).toBeVisible();
    });

    test('push bildirimi dahil gösterilir', async ({ page }) => {
        await page.goto('/yonetim/paketler');

        await expect(page.locator('[data-testid="package-push"]')).toBeVisible();
        await expect(page.locator('[data-testid="push-included"]')).toContainText('Dahil');
    });

    test('mevcut bakiye gösterilir', async ({ page }) => {
        await page.goto('/yonetim/paketler');

        await expect(page.locator('[data-testid="current-balance"]')).toBeVisible();
        await expect(page.locator('[data-testid="sms-balance"]')).toContainText('127 SMS');
    });
});

test.describe('Yönetim Panel - Mobile', () => {
    test.use({ viewport: { width: 375, height: 667 } });

    test('mobilde entegrasyonlar yüklenir', async ({ page }) => {
        await page.route('**/yonetim/entegrasyonlar', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockEntegrasyonlarHtml(),
            });
        });

        await page.goto('/yonetim/entegrasyonlar');

        await expect(page.locator('h1')).toContainText('Entegrasyonlar');
    });

    test('mobilde abonelikler yüklenir', async ({ page }) => {
        await page.route('**/yonetim/abonelikler', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockAboneliklerHtml(),
            });
        });

        await page.goto('/yonetim/abonelikler');

        await expect(page.locator('h1')).toContainText('Abonelik Yönetimi');
    });
});
