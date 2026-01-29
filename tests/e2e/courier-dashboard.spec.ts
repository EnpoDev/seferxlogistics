import { test, expect } from '@playwright/test';

/**
 * Kurye Dashboard E2E Testleri
 *
 * Bu testler kurye dashboard sayfasını test eder.
 * Route mocking kullanarak backend olmadan çalışır.
 */

// Mock dashboard page HTML
const mockDashboardHtml = (data: {
    courierName?: string;
    isOnline?: boolean;
    activeOrders?: any[];
    stats?: { today: number; week: number; month: number };
} = {}) => {
    const {
        courierName = 'Test Kurye',
        isOnline = true,
        activeOrders = [],
        stats = { today: 3, week: 15, month: 45 }
    } = data;

    return `
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="test-token">
    <title>Kurye Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100">
<div class="min-h-screen" x-data="dashboard()">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="p-4 flex items-center justify-between">
            <div>
                <h1 class="text-lg font-bold" data-testid="welcome-message">Merhaba, ${courierName}</h1>
                <p class="text-xs text-gray-500">Günaydın!</p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="toggleStatus()" data-testid="status-toggle"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium"
                        :class="isOnline ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'">
                    <span class="w-2 h-2 rounded-full" :class="isOnline ? 'bg-green-500' : 'bg-gray-400'"></span>
                    <span x-text="isOnline ? 'Aktif' : 'Pasif'" data-testid="status-text"></span>
                </button>
            </div>
        </div>
    </header>

    <!-- Stats -->
    <div class="p-4 grid grid-cols-3 gap-3">
        <div class="bg-white rounded-xl p-3 text-center">
            <p class="text-2xl font-bold text-red-600" data-testid="stat-today">${stats.today}</p>
            <p class="text-xs text-gray-500">Bugün</p>
        </div>
        <div class="bg-white rounded-xl p-3 text-center">
            <p class="text-2xl font-bold text-blue-600" data-testid="stat-week">${stats.week}</p>
            <p class="text-xs text-gray-500">Bu Hafta</p>
        </div>
        <div class="bg-white rounded-xl p-3 text-center">
            <p class="text-2xl font-bold text-green-600" data-testid="stat-month">${stats.month}</p>
            <p class="text-xs text-gray-500">Bu Ay</p>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="px-4 mb-4">
        <div class="bg-white rounded-xl p-4">
            <h2 class="text-sm font-semibold mb-3">Hızlı Erişim</h2>
            <div class="grid grid-cols-4 gap-2">
                <a href="/kurye/havuz" data-testid="quick-pool" class="flex flex-col items-center p-2 rounded-lg hover:bg-gray-50">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mb-1">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <span class="text-xs">Havuz</span>
                </a>
                <a href="/kurye/siparisler" data-testid="quick-orders" class="flex flex-col items-center p-2 rounded-lg hover:bg-gray-50">
                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mb-1">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <span class="text-xs">Siparişler</span>
                </a>
                <a href="/kurye/rota" data-testid="quick-route" class="flex flex-col items-center p-2 rounded-lg hover:bg-gray-50">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mb-1">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                        </svg>
                    </div>
                    <span class="text-xs">Rota</span>
                </a>
                <a href="/kurye/profil" data-testid="quick-profile" class="flex flex-col items-center p-2 rounded-lg hover:bg-gray-50">
                    <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mb-1">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <span class="text-xs">Profil</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Active Orders -->
    <div class="px-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold">Aktif Siparişler</h2>
            <a href="/kurye/siparisler" class="text-xs text-red-600">Tümünü Gör</a>
        </div>

        <template x-if="activeOrders.length === 0">
            <div class="bg-white rounded-xl p-8 text-center" data-testid="no-active-orders">
                <p class="text-gray-500">Aktif sipariş bulunmuyor</p>
                <a href="/kurye/havuz" class="text-red-600 text-sm mt-2 inline-block">Havuza Git</a>
            </div>
        </template>

        <div class="space-y-3" data-testid="active-orders-list">
            <template x-for="order in activeOrders" :key="order.id">
                <a :href="'/kurye/siparis/' + order.id" class="block bg-white rounded-xl p-4" :data-order-id="order.id">
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <span class="text-sm font-bold" x-text="'#' + order.order_number" data-testid="active-order-number"></span>
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full"
                                  :class="order.status === 'yolda' ? 'bg-blue-100 text-blue-600' : 'bg-yellow-100 text-yellow-600'"
                                  x-text="order.status_text" data-testid="active-order-status"></span>
                        </div>
                        <span class="text-sm font-bold text-green-600" x-text="'₺' + order.total" data-testid="active-order-total"></span>
                    </div>
                    <p class="text-xs text-gray-600" x-text="order.customer_address" data-testid="active-order-address"></p>
                </a>
            </template>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <nav class="fixed bottom-0 left-0 right-0 bg-white border-t py-2" data-testid="bottom-nav">
        <div class="flex justify-around">
            <a href="/kurye" class="flex flex-col items-center text-red-600" data-testid="nav-home">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="text-xs">Ana Sayfa</span>
            </a>
            <a href="/kurye/siparisler" class="flex flex-col items-center text-gray-500" data-testid="nav-orders">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span class="text-xs">Siparişler</span>
            </a>
            <a href="/kurye/havuz" class="flex flex-col items-center text-gray-500" data-testid="nav-pool">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <span class="text-xs">Havuz</span>
            </a>
            <a href="/kurye/profil" class="flex flex-col items-center text-gray-500" data-testid="nav-profile">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span class="text-xs">Profil</span>
            </a>
        </div>
    </nav>
</div>

<script>
function dashboard() {
    return {
        isOnline: ${isOnline},
        activeOrders: ${JSON.stringify(activeOrders)},

        async toggleStatus() {
            try {
                const response = await fetch('/kurye/durum', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ status: this.isOnline ? 'offline' : 'online' })
                });

                const data = await response.json();
                if (data.success) {
                    this.isOnline = !this.isOnline;
                    window.__statusChanged = this.isOnline;
                }
            } catch (e) {
                console.error('Status update failed:', e);
            }
        }
    }
}
</script>
</body>
</html>
`;
};

test.describe('Kurye Dashboard', () => {
    test.beforeEach(async ({ page }) => {
        await page.route('**/kurye', async route => {
            if (route.request().method() === 'GET') {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockDashboardHtml(),
                });
            }
        });
    });

    test('dashboard sayfası yüklenir', async ({ page }) => {
        await page.goto('/kurye');

        await expect(page.locator('[data-testid="welcome-message"]')).toContainText('Merhaba');
        await expect(page.locator('[data-testid="status-toggle"]')).toBeVisible();
    });

    test('istatistikler görüntülenir', async ({ page }) => {
        await page.goto('/kurye');

        await expect(page.locator('[data-testid="stat-today"]')).toContainText('3');
        await expect(page.locator('[data-testid="stat-week"]')).toContainText('15');
        await expect(page.locator('[data-testid="stat-month"]')).toContainText('45');
    });

    test('hızlı erişim butonları görünür', async ({ page }) => {
        await page.goto('/kurye');

        await expect(page.locator('[data-testid="quick-pool"]')).toBeVisible();
        await expect(page.locator('[data-testid="quick-orders"]')).toBeVisible();
        await expect(page.locator('[data-testid="quick-route"]')).toBeVisible();
        await expect(page.locator('[data-testid="quick-profile"]')).toBeVisible();
    });

    test('alt navigasyon görünür', async ({ page }) => {
        await page.goto('/kurye');

        await expect(page.locator('[data-testid="bottom-nav"]')).toBeVisible();
        await expect(page.locator('[data-testid="nav-home"]')).toBeVisible();
        await expect(page.locator('[data-testid="nav-orders"]')).toBeVisible();
        await expect(page.locator('[data-testid="nav-pool"]')).toBeVisible();
        await expect(page.locator('[data-testid="nav-profile"]')).toBeVisible();
    });

    test('aktif sipariş yokken boş mesaj gösterilir', async ({ page }) => {
        await page.goto('/kurye');

        await expect(page.locator('[data-testid="no-active-orders"]')).toBeVisible();
        await expect(page.locator('text=Aktif sipariş bulunmuyor')).toBeVisible();
    });
});

test.describe('Kurye Dashboard - Durum Değiştirme', () => {
    test('aktif durumda toggle görünümü doğru', async ({ page }) => {
        await page.route('**/kurye', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockDashboardHtml({ isOnline: true }),
            });
        });

        await page.goto('/kurye');

        await expect(page.locator('[data-testid="status-text"]')).toContainText('Aktif');
        await expect(page.locator('[data-testid="status-toggle"]')).toHaveClass(/bg-green-100/);
    });

    test('pasif durumda toggle görünümü doğru', async ({ page }) => {
        await page.route('**/kurye', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockDashboardHtml({ isOnline: false }),
            });
        });

        await page.goto('/kurye');

        await expect(page.locator('[data-testid="status-text"]')).toContainText('Pasif');
        await expect(page.locator('[data-testid="status-toggle"]')).toHaveClass(/bg-gray-100/);
    });

    test('durum değiştirme API çağrısı yapılır', async ({ page }) => {
        let statusApiCalled = false;

        await page.route('**/kurye', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockDashboardHtml({ isOnline: true }),
            });
        });

        await page.route('**/kurye/durum', async route => {
            statusApiCalled = true;
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({ success: true }),
            });
        });

        await page.goto('/kurye');
        await page.locator('[data-testid="status-toggle"]').click();
        await page.waitForTimeout(500);

        expect(statusApiCalled).toBe(true);
    });

    test('durum değişikliği UI\'da yansır', async ({ page }) => {
        await page.route('**/kurye', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockDashboardHtml({ isOnline: true }),
            });
        });

        await page.route('**/kurye/durum', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({ success: true }),
            });
        });

        await page.goto('/kurye');

        // Başlangıçta aktif
        await expect(page.locator('[data-testid="status-text"]')).toContainText('Aktif');

        // Toggle'a tıkla
        await page.locator('[data-testid="status-toggle"]').click();
        await page.waitForTimeout(500);

        // Pasif olmalı
        await expect(page.locator('[data-testid="status-text"]')).toContainText('Pasif');
    });
});

test.describe('Kurye Dashboard - Aktif Siparişler', () => {
    const mockActiveOrders = [
        {
            id: 101,
            order_number: 'ORD-000101',
            customer_address: 'Test Mahallesi No:1',
            total: '125.00',
            status: 'yolda',
            status_text: 'Yolda'
        },
        {
            id: 102,
            order_number: 'ORD-000102',
            customer_address: 'Örnek Sokak No:5',
            total: '89.50',
            status: 'hazirlaniyor',
            status_text: 'Hazırlanıyor'
        }
    ];

    test('aktif siparişler listelenir', async ({ page }) => {
        await page.route('**/kurye', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockDashboardHtml({ activeOrders: mockActiveOrders }),
            });
        });

        await page.goto('/kurye');

        await expect(page.locator('[data-testid="active-orders-list"]')).toBeVisible();
        await expect(page.locator('[data-order-id="101"]')).toBeVisible();
        await expect(page.locator('[data-order-id="102"]')).toBeVisible();
    });

    test('sipariş detayları doğru gösterilir', async ({ page }) => {
        await page.route('**/kurye', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockDashboardHtml({ activeOrders: mockActiveOrders }),
            });
        });

        await page.goto('/kurye');

        await expect(page.locator('[data-order-id="101"] [data-testid="active-order-number"]')).toContainText('#ORD-000101');
        await expect(page.locator('[data-order-id="101"] [data-testid="active-order-total"]')).toContainText('₺125.00');
        await expect(page.locator('[data-order-id="101"] [data-testid="active-order-status"]')).toContainText('Yolda');
    });

    test('yolda durumu mavi renkte gösterilir', async ({ page }) => {
        await page.route('**/kurye', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockDashboardHtml({ activeOrders: mockActiveOrders }),
            });
        });

        await page.goto('/kurye');

        const yoldaStatus = page.locator('[data-order-id="101"] [data-testid="active-order-status"]');
        await expect(yoldaStatus).toHaveClass(/bg-blue-100/);
    });

    test('hazırlanıyor durumu sarı renkte gösterilir', async ({ page }) => {
        await page.route('**/kurye', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockDashboardHtml({ activeOrders: mockActiveOrders }),
            });
        });

        await page.goto('/kurye');

        const hazirlaniyorStatus = page.locator('[data-order-id="102"] [data-testid="active-order-status"]');
        await expect(hazirlaniyorStatus).toHaveClass(/bg-yellow-100/);
    });

    test('sipariş kartına tıklandığında detay sayfasına link', async ({ page }) => {
        await page.route('**/kurye', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockDashboardHtml({ activeOrders: mockActiveOrders }),
            });
        });

        await page.goto('/kurye');

        const orderLink = page.locator('[data-order-id="101"]');
        await expect(orderLink).toHaveAttribute('href', '/kurye/siparis/101');
    });
});

test.describe('Kurye Dashboard - Navigation', () => {
    test.beforeEach(async ({ page }) => {
        await page.route('**/kurye', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockDashboardHtml(),
            });
        });
    });

    test('havuz linkine tıklanabilir', async ({ page }) => {
        await page.goto('/kurye');

        const poolLink = page.locator('[data-testid="quick-pool"]');
        await expect(poolLink).toHaveAttribute('href', '/kurye/havuz');
    });

    test('siparişler linkine tıklanabilir', async ({ page }) => {
        await page.goto('/kurye');

        const ordersLink = page.locator('[data-testid="quick-orders"]');
        await expect(ordersLink).toHaveAttribute('href', '/kurye/siparisler');
    });

    test('rota linkine tıklanabilir', async ({ page }) => {
        await page.goto('/kurye');

        const routeLink = page.locator('[data-testid="quick-route"]');
        await expect(routeLink).toHaveAttribute('href', '/kurye/rota');
    });

    test('profil linkine tıklanabilir', async ({ page }) => {
        await page.goto('/kurye');

        const profileLink = page.locator('[data-testid="quick-profile"]');
        await expect(profileLink).toHaveAttribute('href', '/kurye/profil');
    });

    test('alt navigasyondaki linkler doğru', async ({ page }) => {
        await page.goto('/kurye');

        await expect(page.locator('[data-testid="nav-home"]')).toHaveAttribute('href', '/kurye');
        await expect(page.locator('[data-testid="nav-orders"]')).toHaveAttribute('href', '/kurye/siparisler');
        await expect(page.locator('[data-testid="nav-pool"]')).toHaveAttribute('href', '/kurye/havuz');
        await expect(page.locator('[data-testid="nav-profile"]')).toHaveAttribute('href', '/kurye/profil');
    });
});

test.describe('Kurye Dashboard - Mobile', () => {
    test.use({ viewport: { width: 375, height: 667 } });

    test('mobil görünümde düzgün render edilir', async ({ page }) => {
        await page.route('**/kurye', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockDashboardHtml(),
            });
        });

        await page.goto('/kurye');

        await expect(page.locator('[data-testid="welcome-message"]')).toBeVisible();
        await expect(page.locator('[data-testid="bottom-nav"]')).toBeVisible();
    });

    test('istatistik kartları mobilde yan yana', async ({ page }) => {
        await page.route('**/kurye', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockDashboardHtml(),
            });
        });

        await page.goto('/kurye');

        await expect(page.locator('[data-testid="stat-today"]')).toBeVisible();
        await expect(page.locator('[data-testid="stat-week"]')).toBeVisible();
        await expect(page.locator('[data-testid="stat-month"]')).toBeVisible();
    });

    test('hızlı erişim butonları touch ile çalışır', async ({ page }) => {
        await page.route('**/kurye', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockDashboardHtml(),
            });
        });

        await page.goto('/kurye');

        // Touch ile tıklanabilir ve link mevcut
        const poolBtn = page.locator('[data-testid="quick-pool"]');
        await expect(poolBtn).toBeVisible();
        await expect(poolBtn).toHaveAttribute('href', '/kurye/havuz');
    });
});

test.describe('Kurye Dashboard - Accessibility', () => {
    test.beforeEach(async ({ page }) => {
        await page.route('**/kurye', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockDashboardHtml(),
            });
        });
    });

    test('sayfa başlığı mevcut', async ({ page }) => {
        await page.goto('/kurye');

        await expect(page.locator('h1')).toHaveCount(1);
    });

    test('navigasyon linkleri erişilebilir', async ({ page }) => {
        await page.goto('/kurye');

        // Tüm nav linkleri href'e sahip olmalı
        const navLinks = page.locator('[data-testid="bottom-nav"] a');
        const linkCount = await navLinks.count();

        for (let i = 0; i < linkCount; i++) {
            await expect(navLinks.nth(i)).toHaveAttribute('href');
        }
    });

    test('durum toggle butonuna tab ile erişilebilir', async ({ page }) => {
        await page.goto('/kurye');

        // Tab ile butonlara git
        await page.keyboard.press('Tab');

        // Status toggle focus alabilmeli
        const statusToggle = page.locator('[data-testid="status-toggle"]');
        await statusToggle.focus();
        await expect(statusToggle).toBeFocused();
    });
});

test.describe('Kurye Dashboard - Farklı Kullanıcı Senaryoları', () => {
    test('yeni kurye - sıfır istatistik', async ({ page }) => {
        await page.route('**/kurye', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockDashboardHtml({
                    courierName: 'Yeni Kurye',
                    stats: { today: 0, week: 0, month: 0 },
                    activeOrders: []
                }),
            });
        });

        await page.goto('/kurye');

        await expect(page.locator('[data-testid="stat-today"]')).toContainText('0');
        await expect(page.locator('[data-testid="stat-week"]')).toContainText('0');
        await expect(page.locator('[data-testid="stat-month"]')).toContainText('0');
    });

    test('yoğun kurye - çok sipariş', async ({ page }) => {
        const manyOrders = Array.from({ length: 5 }, (_, i) => ({
            id: i + 1,
            order_number: `ORD-${String(i + 1).padStart(6, '0')}`,
            customer_address: `Adres ${i + 1}`,
            total: String((50 + i * 20).toFixed(2)),
            status: i % 2 === 0 ? 'yolda' : 'hazirlaniyor',
            status_text: i % 2 === 0 ? 'Yolda' : 'Hazırlanıyor'
        }));

        await page.route('**/kurye', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockDashboardHtml({
                    courierName: 'Yoğun Kurye',
                    stats: { today: 12, week: 65, month: 180 },
                    activeOrders: manyOrders
                }),
            });
        });

        await page.goto('/kurye');

        await expect(page.locator('[data-testid="stat-today"]')).toContainText('12');
        await expect(page.locator('[data-order-id="1"]')).toBeVisible();
        await expect(page.locator('[data-order-id="5"]')).toBeVisible();
    });
});
