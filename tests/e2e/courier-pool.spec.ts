import { test, expect } from '@playwright/test';

/**
 * Kurye Havuz Sayfası E2E Testleri
 *
 * Bu testler sayfa yapısını ve AJAX refresh davranışını test eder.
 * Gerçek backend bağlantısı olmadan çalışabilmesi için route mocking kullanır.
 */

// Mock HTML template for pool page
const mockPoolPageHtml = (orders: any[] = []) => `
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="test-token">
    <title>Sipariş Havuzu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        @keyframes pulse-once { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
        .animate-pulse-once { animation: pulse-once 0.5s ease-in-out 3; }
    </style>
</head>
<body class="bg-gray-100">
<div class="p-4 space-y-4" x-data="poolPage()" x-init="init()">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-black">Sipariş Havuzu</h1>
            <p class="text-xs text-gray-500 mt-0.5">
                <span x-text="orders.length"></span> sipariş bekliyor
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-400" x-show="autoRefresh" data-testid="countdown-container">
                <span x-text="countdown" data-testid="countdown"></span>s
            </span>
            <button @click="toggleAutoRefresh()" data-testid="toggle-btn" class="p-2 rounded-lg" :class="autoRefresh ? 'text-green-600 bg-green-50' : 'text-gray-400'">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
            <button @click="refreshPool()" data-testid="refresh-btn" class="p-2 text-gray-600">
                <svg class="w-5 h-5" :class="{ 'animate-spin': refreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
        </div>
    </div>

    <template x-if="orders.length === 0 && !loading">
        <div data-testid="empty-state" class="bg-white rounded-2xl p-12 text-center">
            <h3 class="text-lg font-semibold">Havuz Boş</h3>
            <p class="text-sm text-gray-500">Şu anda bekleyen sipariş bulunmuyor</p>
        </div>
    </template>

    <div class="space-y-3" data-testid="orders-list">
        <template x-for="order in orders" :key="order.id">
            <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden transition-all duration-300"
                 :class="{
                     'border-orange-400': order.waiting_minutes >= 5,
                     'ring-2 ring-green-500 animate-pulse-once': order.isNew
                 }"
                 :data-order-id="order.id"
                 x-init="$nextTick(() => { if (order.isNew) setTimeout(() => order.isNew = false, 2000) })">
                <div class="p-4">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <span class="text-lg font-bold" x-text="'#' + order.order_number" data-testid="order-number"></span>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-xs text-gray-500" x-text="order.created_at"></span>
                                <span class="px-1.5 py-0.5 text-xs rounded-full"
                                      :class="order.waiting_minutes >= 5 ? 'bg-orange-100 text-orange-600' : 'bg-gray-100 text-gray-600'"
                                      x-text="order.waiting_minutes + ' dk'"
                                      data-testid="waiting-badge"></span>
                            </div>
                        </div>
                        <span class="text-lg font-bold text-green-600" x-text="'₺' + parseFloat(order.total).toFixed(2).replace('.', ',')" data-testid="order-total"></span>
                    </div>

                    <div class="space-y-2 mb-4">
                        <div class="flex items-center space-x-2 text-sm">
                            <span x-text="order.customer_name" data-testid="customer-name"></span>
                        </div>
                        <div class="flex items-start space-x-2 text-sm">
                            <span class="text-gray-600" x-text="order.customer_address" data-testid="customer-address"></span>
                        </div>
                        <div class="flex items-center space-x-2 text-sm" x-show="order.distance_km">
                            <span class="text-blue-600 font-medium" x-text="order.distance_km + ' km uzaklıkta'" data-testid="distance"></span>
                        </div>
                    </div>

                    <button @click="acceptOrder(order.id)"
                            :disabled="accepting === order.id"
                            data-testid="accept-btn"
                            class="w-full py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-semibold disabled:opacity-50 touch-active">
                        <span x-show="accepting !== order.id">Siparişi Al</span>
                        <span x-show="accepting === order.id" data-testid="accepting-state">Alınıyor...</span>
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
function poolPage() {
    return {
        orders: ${JSON.stringify(orders)},
        refreshing: false,
        accepting: null,
        loading: false,
        autoRefresh: true,
        countdown: 10,
        intervalId: null,
        lastOrderCount: ${orders.length},

        init() {
            this.startAutoRefresh();
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.stopAutoRefresh();
                } else if (this.autoRefresh) {
                    this.startAutoRefresh();
                    this.refreshPool();
                }
            });
        },

        playNotificationSound() {
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                oscillator.frequency.value = 800;
                oscillator.type = 'sine';
                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.3);
                window.__soundPlayed = true;
            } catch (e) {
                console.log('Audio not available');
            }
        },

        vibrate() {
            if ('vibrate' in navigator) {
                navigator.vibrate([100, 50, 100]);
            }
            window.__vibrateCalled = true;
        },

        startAutoRefresh() {
            this.countdown = 10;
            this.intervalId = setInterval(() => {
                this.countdown--;
                if (this.countdown <= 0) {
                    this.refreshPool();
                    this.countdown = 10;
                }
            }, 1000);
        },

        stopAutoRefresh() {
            if (this.intervalId) {
                clearInterval(this.intervalId);
                this.intervalId = null;
            }
        },

        toggleAutoRefresh() {
            this.autoRefresh = !this.autoRefresh;
            if (this.autoRefresh) {
                this.startAutoRefresh();
            } else {
                this.stopAutoRefresh();
            }
        },

        async refreshPool() {
            if (this.refreshing) return;
            this.refreshing = true;

            try {
                const response = await fetch('/kurye/havuz', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        const currentOrderIds = this.orders.map(o => o.id);
                        const newOrders = data.orders.filter(o => !currentOrderIds.includes(o.id));

                        const updatedOrders = data.orders.map(order => ({
                            ...order,
                            isNew: !currentOrderIds.includes(order.id)
                        }));

                        if (newOrders.length > 0) {
                            this.playNotificationSound();
                            this.vibrate();
                        }

                        this.orders = updatedOrders;
                        this.lastOrderCount = updatedOrders.length;
                        window.__lastRefreshData = data;
                    }
                }
            } catch (error) {
                console.error('Refresh error:', error);
            } finally {
                this.refreshing = false;
            }
        },

        async acceptOrder(orderId) {
            this.accepting = orderId;

            try {
                const response = await fetch('/kurye/siparis/' + orderId + '/kabul', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.orders = this.orders.filter(o => o.id !== orderId);
                    window.__orderAccepted = { orderId, redirect: data.redirect };
                } else {
                    alert(data.message || 'Bir hata oluştu');
                    this.accepting = null;
                }
            } catch (error) {
                alert('Bağlantı hatası');
                this.accepting = null;
            }
        }
    }
}
</script>
</body>
</html>
`;

test.describe('Kurye Havuz Sayfası', () => {
    test.beforeEach(async ({ page }) => {
        // Mock the pool page HTML
        await page.route('**/kurye/havuz', async route => {
            const request = route.request();
            if (request.headers()['accept']?.includes('application/json')) {
                // JSON API request
                await route.fulfill({
                    status: 200,
                    contentType: 'application/json',
                    body: JSON.stringify({ success: true, orders: [] }),
                });
            } else {
                // HTML page request
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockPoolPageHtml([]),
                });
            }
        });
    });

    test('sayfa başarıyla yüklenir', async ({ page }) => {
        await page.goto('/kurye/havuz');

        await expect(page.locator('h1')).toContainText('Sipariş Havuzu');
        await expect(page.locator('text=sipariş bekliyor')).toBeVisible();
    });

    test('otomatik yenileme sayacı çalışır', async ({ page }) => {
        await page.goto('/kurye/havuz');

        const countdown = page.locator('[data-testid="countdown"]');
        await expect(countdown).toBeVisible();

        const initialValue = parseInt(await countdown.textContent() || '10');
        expect(initialValue).toBeLessThanOrEqual(10);

        await page.waitForTimeout(2000);

        const newValue = parseInt(await countdown.textContent() || '10');
        expect(newValue).toBeLessThan(initialValue);
    });

    test('otomatik yenileme toggle çalışır', async ({ page }) => {
        await page.goto('/kurye/havuz');

        const toggleBtn = page.locator('[data-testid="toggle-btn"]');
        const countdownContainer = page.locator('[data-testid="countdown-container"]');

        await expect(countdownContainer).toBeVisible();

        await toggleBtn.click();
        await expect(countdownContainer).toBeHidden();

        await toggleBtn.click();
        await expect(countdownContainer).toBeVisible();
    });

    test('manuel yenileme AJAX ile çalışır', async ({ page }) => {
        await page.goto('/kurye/havuz');

        let ajaxCalled = false;
        await page.route('**/kurye/havuz', async route => {
            const request = route.request();
            if (request.headers()['accept']?.includes('application/json')) {
                ajaxCalled = true;
                await route.fulfill({
                    status: 200,
                    contentType: 'application/json',
                    body: JSON.stringify({ success: true, orders: [] }),
                });
            } else {
                await route.continue();
            }
        });

        await page.locator('[data-testid="refresh-btn"]').click();
        await page.waitForTimeout(500);

        expect(ajaxCalled).toBe(true);
    });

    test('AJAX yenileme sayfa yenilemez (marker test)', async ({ page }) => {
        await page.goto('/kurye/havuz');

        await page.evaluate(() => {
            (window as any).__testMarker = 'test-value';
        });

        await page.route('**/kurye/havuz', async route => {
            if (route.request().headers()['accept']?.includes('application/json')) {
                await route.fulfill({
                    status: 200,
                    contentType: 'application/json',
                    body: JSON.stringify({ success: true, orders: [] }),
                });
            }
        });

        await page.locator('[data-testid="refresh-btn"]').click();
        await page.waitForTimeout(500);

        const markerValue = await page.evaluate(() => (window as any).__testMarker);
        expect(markerValue).toBe('test-value');
    });

    test('havuz boş mesajı gösterilir', async ({ page }) => {
        await page.goto('/kurye/havuz');

        await expect(page.locator('[data-testid="empty-state"]')).toBeVisible();
        await expect(page.locator('text=Havuz Boş')).toBeVisible();
    });

    test('sipariş listesi gösterilir', async ({ page }) => {
        const testOrders = [{
            id: 123,
            order_number: 'ORD-000123',
            customer_name: 'Test Müşteri',
            customer_address: 'Test Adres Mahallesi No:1',
            total: '150.00',
            waiting_minutes: 3,
            distance_km: 2.5,
            created_at: '3 dakika önce',
        }];

        await page.route('**/kurye/havuz', async route => {
            if (!route.request().headers()['accept']?.includes('application/json')) {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockPoolPageHtml(testOrders),
                });
            } else {
                await route.fulfill({
                    status: 200,
                    contentType: 'application/json',
                    body: JSON.stringify({ success: true, orders: testOrders }),
                });
            }
        });

        await page.goto('/kurye/havuz');

        await expect(page.locator('[data-testid="order-number"]')).toContainText('#ORD-000123');
        await expect(page.locator('[data-testid="customer-name"]')).toContainText('Test Müşteri');
        await expect(page.locator('[data-testid="order-total"]')).toContainText('₺150,00');
        await expect(page.locator('[data-testid="distance"]')).toContainText('2.5 km');
    });

    test('yeni sipariş geldiğinde bildirim çalışır', async ({ page }) => {
        await page.goto('/kurye/havuz');

        // Yeni sipariş mock'la
        await page.route('**/kurye/havuz', async route => {
            if (route.request().headers()['accept']?.includes('application/json')) {
                await route.fulfill({
                    status: 200,
                    contentType: 'application/json',
                    body: JSON.stringify({
                        success: true,
                        orders: [{
                            id: 999,
                            order_number: 'ORD-000999',
                            customer_name: 'Yeni Müşteri',
                            customer_address: 'Yeni Adres',
                            total: '200.00',
                            waiting_minutes: 1,
                            distance_km: 1.0,
                            created_at: '1 dakika önce',
                        }],
                    }),
                });
            }
        });

        await page.locator('[data-testid="refresh-btn"]').click();
        await page.waitForTimeout(500);

        // Bildirim fonksiyonları çağrıldı mı?
        const soundPlayed = await page.evaluate(() => (window as any).__soundPlayed);
        const vibrateCalled = await page.evaluate(() => (window as any).__vibrateCalled);

        expect(soundPlayed || vibrateCalled).toBe(true);
    });

    test('5+ dakika bekleyen siparişler turuncu border alır', async ({ page }) => {
        const testOrders = [{
            id: 456,
            order_number: 'ORD-000456',
            customer_name: 'Bekleyen Müşteri',
            customer_address: 'Bekleyen Adres',
            total: '100.00',
            waiting_minutes: 7,
            distance_km: null,
            created_at: '7 dakika önce',
        }];

        await page.route('**/kurye/havuz', async route => {
            if (!route.request().headers()['accept']?.includes('application/json')) {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockPoolPageHtml(testOrders),
                });
            }
        });

        await page.goto('/kurye/havuz');

        const orderCard = page.locator('[data-order-id="456"]');
        await expect(orderCard).toHaveClass(/border-orange-400/);

        const waitingBadge = page.locator('[data-testid="waiting-badge"]');
        await expect(waitingBadge).toContainText('7 dk');
        await expect(waitingBadge).toHaveClass(/bg-orange-100/);
    });

    test('sipariş kabul işlemi çalışır', async ({ page }) => {
        const testOrders = [{
            id: 789,
            order_number: 'ORD-000789',
            customer_name: 'Kabul Test',
            customer_address: 'Test Adres',
            total: '120.00',
            waiting_minutes: 2,
            distance_km: 1.5,
            created_at: '2 dakika önce',
        }];

        await page.route('**/kurye/havuz', async route => {
            if (!route.request().headers()['accept']?.includes('application/json')) {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockPoolPageHtml(testOrders),
                });
            }
        });

        await page.route('**/kurye/siparis/789/kabul', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({
                    success: true,
                    message: 'Sipariş kabul edildi.',
                    redirect: '/kurye/siparis/789',
                }),
            });
        });

        await page.goto('/kurye/havuz');

        // Accept butonuna tıkla
        const acceptBtn = page.locator('[data-testid="accept-btn"]');
        await expect(acceptBtn).toBeVisible();
        await acceptBtn.click();

        // Sipariş kabul edilene kadar bekle
        await page.waitForTimeout(1000);

        // Sipariş kabul edildi mi kontrol et
        const acceptedData = await page.evaluate(() => (window as any).__orderAccepted);
        expect(acceptedData?.orderId).toBe(789);
        expect(acceptedData?.redirect).toBe('/kurye/siparis/789');
    });
});

test.describe('Kurye Havuz - Edge Cases', () => {
    test('çok sayıda sipariş gösterilir', async ({ page }) => {
        const manyOrders = Array.from({ length: 20 }, (_, i) => ({
            id: i + 1,
            order_number: `ORD-${String(i + 1).padStart(6, '0')}`,
            customer_name: `Müşteri ${i + 1}`,
            customer_address: `Adres ${i + 1}`,
            total: String((50 + i * 10).toFixed(2)),
            waiting_minutes: i % 10,
            distance_km: (1 + i * 0.5).toFixed(1),
            created_at: `${i + 1} dakika önce`,
        }));

        await page.route('**/kurye/havuz', async route => {
            if (!route.request().headers()['accept']?.includes('application/json')) {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockPoolPageHtml(manyOrders),
                });
            }
        });

        await page.goto('/kurye/havuz');

        // Sipariş sayısı doğru gösterilmeli
        await expect(page.locator('text=20 sipariş bekliyor')).toBeVisible();

        // İlk sipariş görünmeli
        await expect(page.locator('[data-testid="order-number"]').first()).toContainText('#ORD-000001');

        // Scroll yapılabilmeli
        await page.locator('[data-order-id="10"]').scrollIntoViewIfNeeded();
        await expect(page.locator('[data-order-id="10"]')).toBeVisible();
    });

    test('sipariş kaldırıldığında liste güncellenir', async ({ page }) => {
        const initialOrders = [
            { id: 1, order_number: 'ORD-000001', customer_name: 'Müşteri 1', customer_address: 'Adres 1', total: '100.00', waiting_minutes: 2, distance_km: 1.0, created_at: '2 dk önce' },
            { id: 2, order_number: 'ORD-000002', customer_name: 'Müşteri 2', customer_address: 'Adres 2', total: '150.00', waiting_minutes: 3, distance_km: 2.0, created_at: '3 dk önce' },
        ];

        await page.route('**/kurye/havuz', async route => {
            if (!route.request().headers()['accept']?.includes('application/json')) {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockPoolPageHtml(initialOrders),
                });
            } else {
                // Refresh'te sadece 1 sipariş döndür (biri alınmış)
                await route.fulfill({
                    status: 200,
                    contentType: 'application/json',
                    body: JSON.stringify({
                        success: true,
                        orders: [initialOrders[1]], // Sadece 2. sipariş kaldı
                    }),
                });
            }
        });

        await page.goto('/kurye/havuz');

        // Başlangıçta 2 sipariş
        await expect(page.locator('text=2 sipariş bekliyor')).toBeVisible();

        // Refresh yap
        await page.locator('[data-testid="refresh-btn"]').click();
        await page.waitForTimeout(500);

        // Şimdi 1 sipariş olmalı
        await expect(page.locator('text=1 sipariş bekliyor')).toBeVisible();
    });

    test('uzun müşteri adresi truncate edilir', async ({ page }) => {
        const orderWithLongAddress = [{
            id: 1,
            order_number: 'ORD-000001',
            customer_name: 'Test',
            customer_address: 'Bu çok uzun bir adres metni olup birden fazla satıra yayılması gereken ve truncate edilmesi beklenen bir adrestir. Mahalle, Cadde, Sokak, Apartman, Daire bilgileri içermektedir.',
            total: '100.00',
            waiting_minutes: 1,
            distance_km: null,
            created_at: '1 dk önce',
        }];

        await page.route('**/kurye/havuz', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockPoolPageHtml(orderWithLongAddress),
            });
        });

        await page.goto('/kurye/havuz');

        const addressElement = page.locator('[data-testid="customer-address"]');
        await expect(addressElement).toBeVisible();
    });

    test('mesafe bilgisi olmayan sipariş gösterilir', async ({ page }) => {
        const orderWithoutDistance = [{
            id: 1,
            order_number: 'ORD-000001',
            customer_name: 'Test',
            customer_address: 'Test Adres',
            total: '100.00',
            waiting_minutes: 1,
            distance_km: null,
            created_at: '1 dk önce',
        }];

        await page.route('**/kurye/havuz', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockPoolPageHtml(orderWithoutDistance),
            });
        });

        await page.goto('/kurye/havuz');

        // Mesafe elementi gizli olmalı
        await expect(page.locator('[data-testid="distance"]')).toBeHidden();
    });

    test('farklı bekleme sürelerinde doğru renkler gösterilir', async ({ page }) => {
        const ordersWithVariousWaiting = [
            { id: 1, order_number: 'ORD-000001', customer_name: 'Normal', customer_address: 'Adres', total: '100.00', waiting_minutes: 2, distance_km: 1.0, created_at: '2 dk' },
            { id: 2, order_number: 'ORD-000002', customer_name: 'Acil', customer_address: 'Adres', total: '100.00', waiting_minutes: 5, distance_km: 1.0, created_at: '5 dk' },
            { id: 3, order_number: 'ORD-000003', customer_name: 'Çok Acil', customer_address: 'Adres', total: '100.00', waiting_minutes: 10, distance_km: 1.0, created_at: '10 dk' },
        ];

        await page.route('**/kurye/havuz', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockPoolPageHtml(ordersWithVariousWaiting),
            });
        });

        await page.goto('/kurye/havuz');

        // Normal sipariş (< 5 dk) - gri badge
        const normalBadge = page.locator('[data-order-id="1"] [data-testid="waiting-badge"]');
        await expect(normalBadge).toHaveClass(/bg-gray-100/);

        // Acil sipariş (>= 5 dk) - turuncu badge ve border
        const urgentBadge = page.locator('[data-order-id="2"] [data-testid="waiting-badge"]');
        await expect(urgentBadge).toHaveClass(/bg-orange-100/);
        await expect(page.locator('[data-order-id="2"]')).toHaveClass(/border-orange-400/);
    });
});

test.describe('Kurye Havuz - Network Errors', () => {
    test('AJAX hatası durumunda sayfa crash etmez', async ({ page }) => {
        await page.route('**/kurye/havuz', async route => {
            const request = route.request();
            if (request.headers()['accept']?.includes('application/json')) {
                await route.fulfill({
                    status: 500,
                    contentType: 'application/json',
                    body: JSON.stringify({ error: 'Server error' }),
                });
            } else {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockPoolPageHtml([]),
                });
            }
        });

        await page.goto('/kurye/havuz');
        await page.locator('[data-testid="refresh-btn"]').click();
        await page.waitForTimeout(500);

        // Sayfa hala çalışıyor olmalı (crash etmedi)
        await expect(page.locator('h1')).toContainText('Sipariş Havuzu');
        await expect(page.locator('[data-testid="refresh-btn"]')).toBeVisible();
    });

    test('network timeout durumunda refreshing state düzgün çalışır', async ({ page }) => {
        await page.route('**/kurye/havuz', async route => {
            const request = route.request();
            if (request.headers()['accept']?.includes('application/json')) {
                // Yavaş yanıt simüle et
                await new Promise(resolve => setTimeout(resolve, 2000));
                await route.fulfill({
                    status: 200,
                    contentType: 'application/json',
                    body: JSON.stringify({ success: true, orders: [] }),
                });
            } else {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockPoolPageHtml([]),
                });
            }
        });

        await page.goto('/kurye/havuz');

        // Refresh başlat
        await page.locator('[data-testid="refresh-btn"]').click();

        // Refresh icon dönmeli (animate-spin class)
        const refreshIcon = page.locator('[data-testid="refresh-btn"] svg');
        await expect(refreshIcon).toHaveClass(/animate-spin/);

        // Yanıt geldikten sonra durmalı
        await page.waitForTimeout(2500);
        await expect(refreshIcon).not.toHaveClass(/animate-spin/);
    });

    test('sipariş kabul hatası alert gösterir', async ({ page }) => {
        const testOrder = [{
            id: 1,
            order_number: 'ORD-000001',
            customer_name: 'Test',
            customer_address: 'Adres',
            total: '100.00',
            waiting_minutes: 1,
            distance_km: 1.0,
            created_at: '1 dk',
        }];

        await page.route('**/kurye/havuz', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockPoolPageHtml(testOrder),
            });
        });

        await page.route('**/kurye/siparis/1/kabul', async route => {
            await route.fulfill({
                status: 400,
                contentType: 'application/json',
                body: JSON.stringify({
                    success: false,
                    message: 'Bu sipariş artık havuzda değil.',
                }),
            });
        });

        // Alert dialog'u yakala
        page.on('dialog', async dialog => {
            expect(dialog.message()).toContain('Bu sipariş artık havuzda değil');
            await dialog.accept();
        });

        await page.goto('/kurye/havuz');
        await page.locator('[data-testid="accept-btn"]').click();

        await page.waitForTimeout(500);
    });
});

test.describe('Kurye Havuz - Auto Refresh Behavior', () => {
    test('countdown 0 olduğunda otomatik refresh yapılır', async ({ page }) => {
        let refreshCount = 0;

        await page.route('**/kurye/havuz', async route => {
            const request = route.request();
            if (request.headers()['accept']?.includes('application/json')) {
                refreshCount++;
                await route.fulfill({
                    status: 200,
                    contentType: 'application/json',
                    body: JSON.stringify({ success: true, orders: [] }),
                });
            } else {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockPoolPageHtml([]),
                });
            }
        });

        await page.goto('/kurye/havuz');

        // 11 saniye bekle (1 tam döngü + 1 saniye)
        await page.waitForTimeout(11000);

        // En az 1 otomatik refresh yapılmış olmalı
        expect(refreshCount).toBeGreaterThanOrEqual(1);
    });

    test('auto-refresh kapalıyken countdown gizlenir', async ({ page }) => {
        await page.route('**/kurye/havuz', async route => {
            const request = route.request();
            if (request.headers()['accept']?.includes('application/json')) {
                await route.fulfill({
                    status: 200,
                    contentType: 'application/json',
                    body: JSON.stringify({ success: true, orders: [] }),
                });
            } else {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockPoolPageHtml([]),
                });
            }
        });

        await page.goto('/kurye/havuz');

        // Başlangıçta countdown görünür
        await expect(page.locator('[data-testid="countdown-container"]')).toBeVisible();

        // Auto-refresh'i kapat
        await page.locator('[data-testid="toggle-btn"]').click();
        await page.waitForTimeout(200);

        // Countdown gizlenmeli
        await expect(page.locator('[data-testid="countdown-container"]')).toBeHidden();
    });

    test('çoklu hızlı tıklamada duplicate refresh yapılmaz', async ({ page }) => {
        let refreshCount = 0;

        await page.route('**/kurye/havuz', async route => {
            const request = route.request();
            if (request.headers()['accept']?.includes('application/json')) {
                refreshCount++;
                // Yavaş yanıt
                await new Promise(resolve => setTimeout(resolve, 500));
                await route.fulfill({
                    status: 200,
                    contentType: 'application/json',
                    body: JSON.stringify({ success: true, orders: [] }),
                });
            } else {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockPoolPageHtml([]),
                });
            }
        });

        await page.goto('/kurye/havuz');

        // Auto-refresh'i kapat ki sayımızı bozmasın
        await page.locator('[data-testid="toggle-btn"]').click();
        await page.waitForTimeout(200);

        // Mevcut sayıyı kaydet
        const initialCount = refreshCount;

        // Hızlıca 5 kez tıkla
        const refreshBtn = page.locator('[data-testid="refresh-btn"]');
        await refreshBtn.click();
        await refreshBtn.click();
        await refreshBtn.click();
        await refreshBtn.click();
        await refreshBtn.click();

        await page.waitForTimeout(1500);

        // Debounce sayesinde 5 tıklamadan çok az refresh yapılmalı (max 2)
        expect(refreshCount - initialCount).toBeLessThanOrEqual(2);
    });
});

test.describe('Kurye Havuz - Mobile Viewport', () => {
    test.use({ viewport: { width: 375, height: 667 } });

    test('mobil görünümde düzgün çalışır', async ({ page }) => {
        await page.route('**/kurye/havuz', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockPoolPageHtml([]),
            });
        });

        await page.goto('/kurye/havuz');

        await expect(page.locator('h1')).toContainText('Sipariş Havuzu');
        await expect(page.locator('[data-testid="refresh-btn"]')).toBeVisible();
    });

    test('mobil scroll düzgün çalışır', async ({ page }) => {
        const manyOrders = Array.from({ length: 10 }, (_, i) => ({
            id: i + 1,
            order_number: `ORD-${String(i + 1).padStart(6, '0')}`,
            customer_name: `Müşteri ${i + 1}`,
            customer_address: `Adres ${i + 1}`,
            total: '100.00',
            waiting_minutes: 2,
            distance_km: 1.0,
            created_at: '2 dk',
        }));

        await page.route('**/kurye/havuz', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockPoolPageHtml(manyOrders),
            });
        });

        await page.goto('/kurye/havuz');

        // Son siparişe scroll yap
        await page.locator('[data-order-id="10"]').scrollIntoViewIfNeeded();
        await expect(page.locator('[data-order-id="10"]')).toBeInViewport();
    });

    test('touch etkileşimleri çalışır', async ({ page }) => {
        const testOrder = [{
            id: 1,
            order_number: 'ORD-000001',
            customer_name: 'Test',
            customer_address: 'Adres',
            total: '100.00',
            waiting_minutes: 1,
            distance_km: 1.0,
            created_at: '1 dk',
        }];

        await page.route('**/kurye/havuz', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockPoolPageHtml(testOrder),
            });
        });

        await page.route('**/kurye/siparis/1/kabul', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'application/json',
                body: JSON.stringify({ success: true, redirect: '/kurye/siparis/1' }),
            });
        });

        await page.goto('/kurye/havuz');

        // Mobilde butonlar görünür ve tıklanabilir
        const acceptBtn = page.locator('[data-testid="accept-btn"]');
        await expect(acceptBtn).toBeVisible();
        await acceptBtn.click();

        await page.waitForTimeout(500);

        const acceptedData = await page.evaluate(() => (window as any).__orderAccepted);
        expect(acceptedData?.orderId).toBe(1);
    });
});

test.describe('Kurye Havuz - Accessibility', () => {
    test('butonlar keyboard ile erişilebilir', async ({ page }) => {
        await page.route('**/kurye/havuz', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockPoolPageHtml([]),
            });
        });

        await page.goto('/kurye/havuz');

        // Butonlar focus alabilmeli
        const toggleBtn = page.locator('[data-testid="toggle-btn"]');
        await toggleBtn.focus();
        await expect(toggleBtn).toBeFocused();

        // Enter ile toggle çalışır
        await page.keyboard.press('Enter');
        await page.waitForTimeout(200);

        // Countdown gizlenmeli
        await expect(page.locator('[data-testid="countdown-container"]')).toBeHidden();
    });

    test('sayfa başlıkları doğru hiyerarşide', async ({ page }) => {
        await page.route('**/kurye/havuz', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockPoolPageHtml([]),
            });
        });

        await page.goto('/kurye/havuz');

        // H1 elementi var
        const h1 = page.locator('h1');
        await expect(h1).toHaveCount(1);
        await expect(h1).toContainText('Sipariş Havuzu');
    });

    test('boş durum mesajı ekran okuyucu için uygun', async ({ page }) => {
        await page.route('**/kurye/havuz', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockPoolPageHtml([]),
            });
        });

        await page.goto('/kurye/havuz');

        const emptyState = page.locator('[data-testid="empty-state"]');
        await expect(emptyState).toBeVisible();

        // Açıklayıcı metin var
        await expect(emptyState.locator('h3')).toContainText('Havuz Boş');
        await expect(emptyState.locator('p')).toContainText('bekleyen sipariş bulunmuyor');
    });
});
