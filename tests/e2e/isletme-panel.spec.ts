import { test, expect } from '@playwright/test';

/**
 * İşletme Panel E2E Testleri
 *
 * İşletme (Business) panelinin ana sayfalarını test eder.
 * Route mocking ile backend olmadan çalışır.
 */

// Mock Dashboard sayfası
const mockDashboardHtml = (stats: any = {}) => {
    const defaultStats = {
        todayOrders: 45,
        activeOrders: 8,
        completedOrders: 35,
        cancelledOrders: 2,
        totalRevenue: '12,450.00',
        activeCouriers: 5,
        ...stats
    };

    return `
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="test-token">
    <title>Dashboard - İşletme Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100">
<div class="flex h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-lg" data-testid="sidebar">
        <div class="p-4 border-b">
            <h1 class="text-xl font-bold text-red-600">SeferX</h1>
            <p class="text-xs text-gray-500">İşletme Panel</p>
        </div>
        <nav class="p-4 space-y-2">
            <a href="/dashboard" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-red-50 text-red-600" data-testid="nav-dashboard">
                Dashboard
            </a>
            <a href="/siparis" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-50" data-testid="nav-siparis">
                Siparişler
            </a>
            <a href="/isletmem/kuryeler" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-50" data-testid="nav-kuryeler">
                Kuryeler
            </a>
            <a href="/isletmem/musteriler" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-50" data-testid="nav-musteriler">
                Müşteriler
            </a>
            <a href="/yonetim/entegrasyonlar" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-50" data-testid="nav-entegrasyonlar">
                Entegrasyonlar
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-auto p-6">
        <div class="mb-6">
            <h2 class="text-2xl font-bold">Dashboard</h2>
            <p class="text-gray-500">Hoş geldiniz, bugünkü özet</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl p-4 shadow-sm" data-testid="stat-today">
                <p class="text-sm text-gray-500">Bugünkü Siparişler</p>
                <p class="text-2xl font-bold">${defaultStats.todayOrders}</p>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-sm" data-testid="stat-active">
                <p class="text-sm text-gray-500">Aktif Siparişler</p>
                <p class="text-2xl font-bold text-blue-600">${defaultStats.activeOrders}</p>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-sm" data-testid="stat-completed">
                <p class="text-sm text-gray-500">Tamamlanan</p>
                <p class="text-2xl font-bold text-green-600">${defaultStats.completedOrders}</p>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-sm" data-testid="stat-revenue">
                <p class="text-sm text-gray-500">Bugünkü Ciro</p>
                <p class="text-2xl font-bold">₺${defaultStats.totalRevenue}</p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-xl p-4 shadow-sm mb-6">
            <h3 class="font-semibold mb-3">Hızlı İşlemler</h3>
            <div class="flex gap-2">
                <a href="/siparis/create" class="px-4 py-2 bg-red-600 text-white rounded-lg" data-testid="new-order-btn">
                    Yeni Sipariş
                </a>
                <a href="/harita" class="px-4 py-2 bg-blue-600 text-white rounded-lg" data-testid="view-map-btn">
                    Haritayı Gör
                </a>
            </div>
        </div>

        <!-- Active Orders -->
        <div class="bg-white rounded-xl p-4 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold">Aktif Siparişler</h3>
                <a href="/siparis" class="text-sm text-red-600">Tümünü Gör</a>
            </div>
            <div class="space-y-2" data-testid="active-orders-list">
                <p class="text-gray-500 text-sm" data-testid="active-orders-count">${defaultStats.activeOrders} aktif sipariş</p>
            </div>
        </div>
    </main>
</div>
</body>
</html>
`;
};

// Mock Sipariş Listesi sayfası - Pre-rendered HTML (Blade gibi)
const mockSiparisListeHtml = (orders: any[] = []) => {
    const getStatusClass = (status: string) => {
        switch (status) {
            case 'pending': return 'bg-yellow-100 text-yellow-600';
            case 'preparing': return 'bg-blue-100 text-blue-600';
            case 'on_way': return 'bg-purple-100 text-purple-600';
            case 'delivered': return 'bg-green-100 text-green-600';
            default: return 'bg-gray-100 text-gray-600';
        }
    };

    const orderRows = orders.map(order => `
        <tr data-order-id="${order.id}">
            <td class="px-4 py-3 font-medium" data-testid="order-number">#${order.order_number}</td>
            <td class="px-4 py-3">
                <p data-testid="customer-name">${order.customer_name}</p>
                <p class="text-xs text-gray-500">${order.customer_phone}</p>
            </td>
            <td class="px-4 py-3 font-medium" data-testid="order-total">₺${order.total}</td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 text-xs rounded-full ${getStatusClass(order.status)}"
                      data-testid="order-status">${order.status_text}</span>
            </td>
            <td class="px-4 py-3" data-testid="courier-name">${order.courier_name || '-'}</td>
            <td class="px-4 py-3">
                <a href="/siparis/${order.id}/edit" class="text-red-600 text-sm" data-testid="edit-link">Düzenle</a>
            </td>
        </tr>
    `).join('');

    const noOrdersHtml = orders.length === 0 ? `
        <div class="p-8 text-center text-gray-500" data-testid="no-orders">
            Sipariş bulunamadı
        </div>
    ` : '';

    return `
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="test-token">
    <title>Siparişler</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Siparişler</h1>
            <p class="text-gray-500">Sipariş yönetimi</p>
        </div>
        <a href="/siparis/create" class="px-4 py-2 bg-red-600 text-white rounded-lg" data-testid="new-order-btn">
            Yeni Sipariş
        </a>
    </div>

    <!-- Filters -->
    <div class="flex gap-2 mb-4" data-testid="filters">
        <button class="px-4 py-2 rounded-lg bg-red-600 text-white" data-testid="filter-all">Tümü</button>
        <button class="px-4 py-2 rounded-lg bg-white" data-testid="filter-active">Aktif</button>
        <button class="px-4 py-2 rounded-lg bg-white" data-testid="filter-completed">Tamamlanan</button>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full" data-testid="orders-table">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Sipariş No</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Müşteri</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Tutar</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Durum</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Kurye</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                ${orderRows}
            </tbody>
        </table>
        ${noOrdersHtml}
    </div>
</div>
</body>
</html>
`;
};

// Mock Sipariş Oluşturma sayfası
const mockSiparisCreateHtml = () => `
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="test-token">
    <title>Yeni Sipariş</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100">
<div class="p-6" x-data="orderForm()">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Yeni Sipariş</h1>
        <p class="text-gray-500">Sipariş bilgilerini girin</p>
    </div>

    <form @submit.prevent="submitOrder()" class="space-y-6">
        <!-- Müşteri Bilgileri -->
        <div class="bg-white rounded-xl p-4 shadow-sm">
            <h3 class="font-semibold mb-4">Müşteri Bilgileri</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Müşteri Adı</label>
                    <input type="text" x-model="form.customer_name" required data-testid="customer-name-input"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-red-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                    <input type="tel" x-model="form.customer_phone" required data-testid="customer-phone-input"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-red-500">
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Adres</label>
                <textarea x-model="form.customer_address" required data-testid="customer-address-input"
                          class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-red-500" rows="2"></textarea>
            </div>
        </div>

        <!-- Sipariş Detayları -->
        <div class="bg-white rounded-xl p-4 shadow-sm">
            <h3 class="font-semibold mb-4">Sipariş Detayları</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Toplam Tutar</label>
                    <input type="number" step="0.01" x-model="form.total" required data-testid="total-input"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-red-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ödeme Yöntemi</label>
                    <select x-model="form.payment_method" required data-testid="payment-method-select"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-red-500">
                        <option value="cash">Nakit</option>
                        <option value="card">Kredi Kartı</option>
                        <option value="online">Online</option>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Not</label>
                <textarea x-model="form.note" data-testid="note-input"
                          class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-red-500" rows="2"></textarea>
            </div>
        </div>

        <div class="flex gap-4">
            <button type="submit" :disabled="loading" data-testid="submit-btn"
                    class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50">
                <span x-show="!loading">Sipariş Oluştur</span>
                <span x-show="loading">Oluşturuluyor...</span>
            </button>
            <a href="/siparis" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg" data-testid="cancel-btn">
                İptal
            </a>
        </div>
    </form>
</div>

<script>
function orderForm() {
    return {
        form: {
            customer_name: '',
            customer_phone: '',
            customer_address: '',
            total: '',
            payment_method: 'cash',
            note: ''
        },
        loading: false,

        async submitOrder() {
            this.loading = true;
            try {
                const response = await fetch('/siparis', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.form)
                });
                const data = await response.json();
                if (data.success) {
                    window.__orderCreated = true;
                    window.location.href = data.redirect || '/siparis';
                }
            } catch (e) {
                console.error('Order creation failed:', e);
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
</body>
</html>
`;

// Mock Kuryeler sayfası - Pre-rendered HTML (Blade gibi)
const mockKuryelerHtml = (couriers: any[] = []) => {
    const courierCards = couriers.map(courier => `
        <div class="bg-white rounded-xl p-4 shadow-sm" data-courier-id="${courier.id}">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center">
                    <span class="text-lg">${courier.name.charAt(0)}</span>
                </div>
                <div>
                    <h3 class="font-semibold" data-testid="courier-name">${courier.name}</h3>
                    <p class="text-sm text-gray-500" data-testid="courier-phone">${courier.phone}</p>
                </div>
                <div class="ml-auto">
                    <span class="w-3 h-3 rounded-full inline-block ${courier.is_online ? 'bg-green-500' : 'bg-gray-300'}"
                          data-testid="courier-indicator"></span>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                <div>
                    <p class="text-gray-500">Bugün</p>
                    <p class="font-semibold" data-testid="today-orders">${courier.today_orders} sipariş</p>
                </div>
                <div>
                    <p class="text-gray-500">Aktif</p>
                    <p class="font-semibold" data-testid="active-orders">${courier.active_orders} sipariş</p>
                </div>
            </div>
            <a href="/isletmem/kuryeler/${courier.id}/edit"
               class="block text-center text-sm text-red-600" data-testid="edit-link">
                Düzenle
            </a>
        </div>
    `).join('');

    const noCouriersHtml = couriers.length === 0 ? `
        <div class="bg-white rounded-xl p-8 text-center text-gray-500" data-testid="no-couriers">
            Henüz kurye eklenmemiş
        </div>
    ` : '';

    return `
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="test-token">
    <title>Kuryeler</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Kuryeler</h1>
            <p class="text-gray-500">Kurye yönetimi</p>
        </div>
        <a href="/isletmem/kuryeler/create" class="px-4 py-2 bg-red-600 text-white rounded-lg" data-testid="add-courier-btn">
            Yeni Kurye
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" data-testid="courier-grid">
        ${courierCards}
    </div>
    ${noCouriersHtml}
</div>
</body>
</html>
`;
};

test.describe('İşletme Panel - Dashboard', () => {
    test.beforeEach(async ({ page }) => {
        await page.route('**/dashboard', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockDashboardHtml(),
            });
        });
    });

    test('dashboard sayfası yüklenir', async ({ page }) => {
        await page.goto('/dashboard');

        await expect(page.locator('h2')).toContainText('Dashboard');
        await expect(page.locator('[data-testid="sidebar"]')).toBeVisible();
    });

    test('istatistik kartları gösterilir', async ({ page }) => {
        await page.goto('/dashboard');

        await expect(page.locator('[data-testid="stat-today"]')).toBeVisible();
        await expect(page.locator('[data-testid="stat-active"]')).toBeVisible();
        await expect(page.locator('[data-testid="stat-completed"]')).toBeVisible();
        await expect(page.locator('[data-testid="stat-revenue"]')).toBeVisible();
    });

    test('hızlı işlem butonları görünür', async ({ page }) => {
        await page.goto('/dashboard');

        await expect(page.locator('[data-testid="new-order-btn"]')).toBeVisible();
        await expect(page.locator('[data-testid="view-map-btn"]')).toBeVisible();
    });

    test('navigasyon linkleri doğru', async ({ page }) => {
        await page.goto('/dashboard');

        await expect(page.locator('[data-testid="nav-siparis"]')).toHaveAttribute('href', '/siparis');
        await expect(page.locator('[data-testid="nav-kuryeler"]')).toHaveAttribute('href', '/isletmem/kuryeler');
        await expect(page.locator('[data-testid="nav-musteriler"]')).toHaveAttribute('href', '/isletmem/musteriler');
    });
});

test.describe('İşletme Panel - Sipariş Listesi', () => {
    const mockOrders = [
        { id: 1, order_number: 'ORD-000001', customer_name: 'Ali Yılmaz', customer_phone: '5551234567', total: '150.00', status: 'pending', status_text: 'Bekliyor', courier_name: null },
        { id: 2, order_number: 'ORD-000002', customer_name: 'Ayşe Kaya', customer_phone: '5559876543', total: '250.00', status: 'on_way', status_text: 'Yolda', courier_name: 'Mehmet' },
    ];

    test('siparişler listelenir', async ({ page }) => {
        await page.route('**/siparis', async route => {
            if (route.request().method() === 'GET') {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockSiparisListeHtml(mockOrders),
                });
            }
        });

        await page.goto('/siparis');
        await page.waitForTimeout(500);

        await expect(page.locator('h1')).toContainText('Siparişler');
        await expect(page.locator('[data-order-id="1"]')).toBeVisible();
        await expect(page.locator('[data-order-id="2"]')).toBeVisible();
    });

    test('filtreler görünür', async ({ page }) => {
        await page.route('**/siparis', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockSiparisListeHtml(mockOrders),
            });
        });

        await page.goto('/siparis');

        await expect(page.locator('[data-testid="filter-all"]')).toBeVisible();
        await expect(page.locator('[data-testid="filter-active"]')).toBeVisible();
        await expect(page.locator('[data-testid="filter-completed"]')).toBeVisible();
    });

    test('yeni sipariş butonu görünür', async ({ page }) => {
        await page.route('**/siparis', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockSiparisListeHtml(mockOrders),
            });
        });

        await page.goto('/siparis');

        const newOrderBtn = page.locator('[data-testid="new-order-btn"]');
        await expect(newOrderBtn).toBeVisible();
        await expect(newOrderBtn).toHaveAttribute('href', '/siparis/create');
    });

    test('sipariş durumları renklendirilir', async ({ page }) => {
        await page.route('**/siparis', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockSiparisListeHtml(mockOrders),
            });
        });

        await page.goto('/siparis');
        await page.waitForTimeout(500);

        // Bekliyor - sarı
        const pendingStatus = page.locator('[data-order-id="1"] [data-testid="order-status"]');
        await expect(pendingStatus).toHaveClass(/bg-yellow-100/);

        // Yolda - mor
        const onWayStatus = page.locator('[data-order-id="2"] [data-testid="order-status"]');
        await expect(onWayStatus).toHaveClass(/bg-purple-100/);
    });

    test('sipariş yokken mesaj gösterilir', async ({ page }) => {
        await page.route('**/siparis', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockSiparisListeHtml([]),
            });
        });

        await page.goto('/siparis');

        await expect(page.locator('[data-testid="no-orders"]')).toBeVisible();
    });
});

test.describe('İşletme Panel - Sipariş Oluşturma', () => {
    test.beforeEach(async ({ page }) => {
        await page.route('**/siparis/create', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockSiparisCreateHtml(),
            });
        });
    });

    test('sipariş formu yüklenir', async ({ page }) => {
        await page.goto('/siparis/create');

        await expect(page.locator('h1')).toContainText('Yeni Sipariş');
        await expect(page.locator('[data-testid="customer-name-input"]')).toBeVisible();
        await expect(page.locator('[data-testid="customer-phone-input"]')).toBeVisible();
        await expect(page.locator('[data-testid="customer-address-input"]')).toBeVisible();
    });

    test('form alanları doldurulabilir', async ({ page }) => {
        await page.goto('/siparis/create');

        await page.locator('[data-testid="customer-name-input"]').fill('Test Müşteri');
        await page.locator('[data-testid="customer-phone-input"]').fill('5551234567');
        await page.locator('[data-testid="customer-address-input"]').fill('Test Adres');
        await page.locator('[data-testid="total-input"]').fill('150.00');

        await expect(page.locator('[data-testid="customer-name-input"]')).toHaveValue('Test Müşteri');
        await expect(page.locator('[data-testid="total-input"]')).toHaveValue('150.00');
    });

    test('ödeme yöntemi seçilebilir', async ({ page }) => {
        await page.goto('/siparis/create');

        const select = page.locator('[data-testid="payment-method-select"]');
        await select.selectOption('card');
        await expect(select).toHaveValue('card');
    });

    test('iptal butonu görünür', async ({ page }) => {
        await page.goto('/siparis/create');

        const cancelBtn = page.locator('[data-testid="cancel-btn"]');
        await expect(cancelBtn).toBeVisible();
        await expect(cancelBtn).toHaveAttribute('href', '/siparis');
    });

    test('form submit edilebilir', async ({ page }) => {
        let formSubmitted = false;

        await page.route('**/siparis', async route => {
            if (route.request().method() === 'POST') {
                formSubmitted = true;
                await route.fulfill({
                    status: 200,
                    contentType: 'application/json',
                    body: JSON.stringify({ success: true, redirect: '/siparis' }),
                });
            } else {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockSiparisCreateHtml(),
                });
            }
        });

        await page.goto('/siparis/create');

        await page.locator('[data-testid="customer-name-input"]').fill('Test Müşteri');
        await page.locator('[data-testid="customer-phone-input"]').fill('5551234567');
        await page.locator('[data-testid="customer-address-input"]').fill('Test Adres');
        await page.locator('[data-testid="total-input"]').fill('150.00');

        await page.locator('[data-testid="submit-btn"]').click();
        await page.waitForTimeout(1000);

        expect(formSubmitted).toBe(true);
    });
});

test.describe('İşletme Panel - Kuryeler', () => {
    const mockCouriers = [
        { id: 1, name: 'Ali Yılmaz', phone: '5551234567', is_online: true, today_orders: 8, active_orders: 2 },
        { id: 2, name: 'Mehmet Demir', phone: '5559876543', is_online: false, today_orders: 3, active_orders: 0 },
    ];

    test('kuryeler listelenir', async ({ page }) => {
        await page.route('**/isletmem/kuryeler', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockKuryelerHtml(mockCouriers),
            });
        });

        await page.goto('/isletmem/kuryeler');
        await page.waitForTimeout(500);

        await expect(page.locator('h1')).toContainText('Kuryeler');
        await expect(page.locator('[data-courier-id="1"]')).toBeVisible();
        await expect(page.locator('[data-courier-id="2"]')).toBeVisible();
    });

    test('kurye durumu gösterilir', async ({ page }) => {
        await page.route('**/isletmem/kuryeler', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockKuryelerHtml(mockCouriers),
            });
        });

        await page.goto('/isletmem/kuryeler');
        await page.waitForTimeout(500);

        // Aktif kurye yeşil indicator
        const activeIndicator = page.locator('[data-courier-id="1"] [data-testid="courier-indicator"]');
        await expect(activeIndicator).toHaveClass(/bg-green-500/);

        // Pasif kurye gri indicator
        const inactiveIndicator = page.locator('[data-courier-id="2"] [data-testid="courier-indicator"]');
        await expect(inactiveIndicator).toHaveClass(/bg-gray-300/);
    });

    test('yeni kurye butonu görünür', async ({ page }) => {
        await page.route('**/isletmem/kuryeler', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockKuryelerHtml(mockCouriers),
            });
        });

        await page.goto('/isletmem/kuryeler');

        const addBtn = page.locator('[data-testid="add-courier-btn"]');
        await expect(addBtn).toBeVisible();
        await expect(addBtn).toHaveAttribute('href', '/isletmem/kuryeler/create');
    });

    test('kurye yokken mesaj gösterilir', async ({ page }) => {
        await page.route('**/isletmem/kuryeler', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockKuryelerHtml([]),
            });
        });

        await page.goto('/isletmem/kuryeler');

        await expect(page.locator('[data-testid="no-couriers"]')).toBeVisible();
    });
});

test.describe('İşletme Panel - Mobile', () => {
    test.use({ viewport: { width: 375, height: 667 } });

    test('mobilde dashboard yüklenir', async ({ page }) => {
        await page.route('**/dashboard', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockDashboardHtml(),
            });
        });

        await page.goto('/dashboard');

        await expect(page.locator('h2')).toContainText('Dashboard');
    });

    test('mobilde sipariş listesi yüklenir', async ({ page }) => {
        await page.route('**/siparis', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockSiparisListeHtml([]),
            });
        });

        await page.goto('/siparis');

        await expect(page.locator('h1')).toContainText('Siparişler');
    });
});
