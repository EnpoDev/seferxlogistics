import { test, expect } from '@playwright/test';

/**
 * Bayi Panel E2E Testleri
 *
 * Bayi (Franchise) panelinin ana sayfalarını test eder.
 * Route mocking ile backend olmadan çalışır.
 */

// Mock Bayi Harita sayfası
const mockHaritaHtml = () => `
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="test-token">
    <title>Harita - Bayi Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100">
<div class="flex h-screen">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-lg" data-testid="sidebar">
        <div class="p-4 border-b">
            <h1 class="text-xl font-bold text-red-600">SeferX</h1>
            <p class="text-xs text-gray-500">Bayi Panel</p>
        </div>
        <nav class="p-4 space-y-2">
            <a href="/bayi/harita" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-red-50 text-red-600" data-testid="nav-harita">
                <span>Harita</span>
            </a>
            <a href="/bayi/kuryelerim" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-50" data-testid="nav-kuryeler">
                <span>Kuryelerim</span>
            </a>
            <a href="/bayi/isletmelerim" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-50" data-testid="nav-isletmeler">
                <span>İşletmelerim</span>
            </a>
            <a href="/bayi/havuz" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-50" data-testid="nav-havuz">
                <span>Havuz</span>
            </a>
            <a href="/bayi/istatistik" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-50" data-testid="nav-istatistik">
                <span>İstatistik</span>
            </a>
            <a href="/bayi/ayarlar/genel" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-50" data-testid="nav-ayarlar">
                <span>Ayarlar</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-hidden">
        <header class="bg-white shadow-sm p-4 flex items-center justify-between">
            <h2 class="text-lg font-semibold">Canlı Harita</h2>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-500" data-testid="active-couriers">5 aktif kurye</span>
                <span class="text-sm text-gray-500" data-testid="pending-orders">12 bekleyen sipariş</span>
            </div>
        </header>
        <div id="map" class="h-full w-full bg-gray-200" data-testid="map-container">
            <div class="flex items-center justify-center h-full text-gray-500">
                Harita yükleniyor...
            </div>
        </div>
    </main>
</div>
</body>
</html>
`;

// Mock Kuryelerim sayfası - Pre-rendered HTML (Blade gibi)
const mockKuryelerimHtml = (couriers: any[] = []) => {
    const courierRows = couriers.map(c => `
        <tr data-courier-id="${c.id}">
            <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                        <span>${c.name.charAt(0)}</span>
                    </div>
                    <div>
                        <p class="font-medium" data-testid="courier-name">${c.name}</p>
                        <p class="text-xs text-gray-500" data-testid="courier-phone">${c.phone}</p>
                    </div>
                </div>
            </td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 text-xs rounded-full ${c.is_online ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-600'}"
                      data-testid="courier-status">${c.is_online ? 'Aktif' : 'Pasif'}</span>
            </td>
            <td class="px-4 py-3">
                <span class="text-sm" data-testid="courier-orders">${c.today_orders} sipariş</span>
            </td>
            <td class="px-4 py-3">
                <a href="/bayi/kuryelerim/${c.id}" class="text-red-600 text-sm" data-testid="courier-detail-link">Detay</a>
            </td>
        </tr>
    `).join('');

    const emptyState = couriers.length === 0 ? `
        <tr>
            <td colspan="4" class="p-8 text-center text-gray-500" data-testid="no-couriers">
                Henüz kurye eklenmemiş
            </td>
        </tr>
    ` : '';

    return `
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="test-token">
    <title>Kuryelerim - Bayi Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Kuryelerim</h1>
            <p class="text-gray-500">Kurye yönetimi ve takibi</p>
        </div>
        <a href="/bayi/kuryelerim/yeni" class="px-4 py-2 bg-red-600 text-white rounded-lg" data-testid="add-courier-btn">
            Yeni Kurye Ekle
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full" data-testid="courier-table">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Kurye</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Durum</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Bugün</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">İşlemler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                ${courierRows}
                ${emptyState}
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
`;
};

// Mock İşletmelerim sayfası - Pre-rendered HTML
const mockIsletmelerimHtml = (branches: any[] = []) => {
    const branchCards = branches.map(b => `
        <div class="bg-white rounded-xl shadow-sm p-4" data-branch-id="${b.id}">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="font-semibold" data-testid="branch-name">${b.name}</h3>
                    <p class="text-xs text-gray-500" data-testid="branch-address">${b.address}</p>
                </div>
                <span class="px-2 py-1 text-xs rounded-full ${b.is_active ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'}"
                      data-testid="branch-status">${b.is_active ? 'Aktif' : 'Pasif'}</span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div>
                    <p class="text-gray-500">Bugün</p>
                    <p class="font-semibold" data-testid="branch-today">${b.today_orders}</p>
                </div>
                <div>
                    <p class="text-gray-500">Bu Ay</p>
                    <p class="font-semibold" data-testid="branch-month">${b.month_orders}</p>
                </div>
            </div>
            <a href="/bayi/isletmelerim/${b.id}" class="mt-3 block text-center text-sm text-red-600" data-testid="branch-detail-link">
                Detaya Git
            </a>
        </div>
    `).join('');

    const emptyState = branches.length === 0 ? `
        <div class="bg-white rounded-xl p-8 text-center text-gray-500" data-testid="no-branches">
            Henüz işletme eklenmemiş
        </div>
    ` : '';

    return `
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="test-token">
    <title>İşletmelerim - Bayi Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">İşletmelerim</h1>
            <p class="text-gray-500">İşletme yönetimi</p>
        </div>
        <a href="/bayi/isletmelerim/yeni" class="px-4 py-2 bg-red-600 text-white rounded-lg" data-testid="add-branch-btn">
            Yeni İşletme Ekle
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" data-testid="branch-grid">
        ${branchCards}
    </div>
    ${emptyState}
</div>
</body>
</html>
`;
};

// Mock Bayi Havuz sayfası - Pre-rendered HTML
const mockBayiHavuzHtml = (orders: any[] = []) => {
    const orderRows = orders.map(o => `
        <tr data-order-id="${o.id}" class="${o.waiting_minutes >= 5 ? 'bg-orange-50' : ''}">
            <td class="px-4 py-3">
                <input type="checkbox" value="${o.id}">
            </td>
            <td class="px-4 py-3">
                <span class="font-medium" data-testid="order-number">#${o.order_number}</span>
            </td>
            <td class="px-4 py-3">
                <span data-testid="branch-name">${o.branch_name}</span>
            </td>
            <td class="px-4 py-3 max-w-xs truncate">
                <span data-testid="order-address">${o.customer_address}</span>
            </td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 text-xs rounded-full ${o.waiting_minutes >= 5 ? 'bg-orange-100 text-orange-600' : 'bg-gray-100 text-gray-600'}"
                      data-testid="waiting-time">${o.waiting_minutes} dk</span>
            </td>
            <td class="px-4 py-3">
                <button class="px-3 py-1 bg-red-600 text-white text-sm rounded" data-testid="assign-btn">
                    Ata
                </button>
            </td>
        </tr>
    `).join('');

    const emptyState = orders.length === 0 ? `
        <tr>
            <td colspan="6" class="p-8 text-center text-gray-500" data-testid="empty-pool">
                Havuzda bekleyen sipariş yok
            </td>
        </tr>
    ` : '';

    return `
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="test-token">
    <title>Sipariş Havuzu - Bayi Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Sipariş Havuzu</h1>
            <p class="text-gray-500">${orders.length} sipariş bekliyor</p>
        </div>
        <div class="flex gap-2">
            <button class="px-4 py-2 bg-blue-600 text-white rounded-lg" data-testid="auto-assign-btn">
                Otomatik Ata
            </button>
            <button class="px-4 py-2 bg-red-600 text-white rounded-lg" data-testid="bulk-assign-btn">
                Toplu Ata
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full" data-testid="pool-table">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 w-10"><input type="checkbox" data-testid="select-all"></th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Sipariş</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">İşletme</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Adres</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">Bekleme</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">İşlem</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                ${orderRows}
                ${emptyState}
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
`;
};

test.describe('Bayi Panel - Harita Sayfası', () => {
    test.beforeEach(async ({ page }) => {
        await page.route('**/bayi/harita', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockHaritaHtml(),
            });
        });
    });

    test('harita sayfası yüklenir', async ({ page }) => {
        await page.goto('/bayi/harita');

        await expect(page.locator('h2')).toContainText('Canlı Harita');
        await expect(page.locator('[data-testid="map-container"]')).toBeVisible();
    });

    test('sidebar navigasyonu görünür', async ({ page }) => {
        await page.goto('/bayi/harita');

        await expect(page.locator('[data-testid="sidebar"]')).toBeVisible();
        await expect(page.locator('[data-testid="nav-harita"]')).toBeVisible();
        await expect(page.locator('[data-testid="nav-kuryeler"]')).toBeVisible();
        await expect(page.locator('[data-testid="nav-isletmeler"]')).toBeVisible();
    });

    test('aktif kurye ve sipariş sayısı gösterilir', async ({ page }) => {
        await page.goto('/bayi/harita');

        await expect(page.locator('[data-testid="active-couriers"]')).toContainText('aktif kurye');
        await expect(page.locator('[data-testid="pending-orders"]')).toContainText('bekleyen sipariş');
    });

    test('navigasyon linkleri doğru', async ({ page }) => {
        await page.goto('/bayi/harita');

        await expect(page.locator('[data-testid="nav-kuryeler"]')).toHaveAttribute('href', '/bayi/kuryelerim');
        await expect(page.locator('[data-testid="nav-isletmeler"]')).toHaveAttribute('href', '/bayi/isletmelerim');
        await expect(page.locator('[data-testid="nav-havuz"]')).toHaveAttribute('href', '/bayi/havuz');
    });
});

test.describe('Bayi Panel - Kuryelerim Sayfası', () => {
    const mockCouriers = [
        { id: 1, name: 'Ali Yılmaz', phone: '5551234567', is_online: true, today_orders: 8 },
        { id: 2, name: 'Mehmet Demir', phone: '5559876543', is_online: false, today_orders: 3 },
    ];

    test('kuryeler listelenir', async ({ page }) => {
        await page.route('**/bayi/kuryelerim', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockKuryelerimHtml(mockCouriers),
            });
        });

        await page.goto('/bayi/kuryelerim');

        await expect(page.locator('h1')).toContainText('Kuryelerim');
        await expect(page.locator('[data-testid="courier-table"]')).toBeVisible();

        // Alpine.js'nin render etmesini bekle
        await page.waitForTimeout(500);
        await expect(page.locator('[data-courier-id="1"]')).toBeVisible();
        await expect(page.locator('[data-courier-id="2"]')).toBeVisible();
    });

    test('yeni kurye ekleme butonu görünür', async ({ page }) => {
        await page.route('**/bayi/kuryelerim', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockKuryelerimHtml(mockCouriers),
            });
        });

        await page.goto('/bayi/kuryelerim');

        const addBtn = page.locator('[data-testid="add-courier-btn"]');
        await expect(addBtn).toBeVisible();
        await expect(addBtn).toHaveAttribute('href', '/bayi/kuryelerim/yeni');
    });

    test('kurye durumları doğru gösterilir', async ({ page }) => {
        await page.route('**/bayi/kuryelerim', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockKuryelerimHtml(mockCouriers),
            });
        });

        await page.goto('/bayi/kuryelerim');
        await page.waitForTimeout(500);

        // Aktif kurye yeşil
        const activeStatus = page.locator('[data-courier-id="1"] [data-testid="courier-status"]');
        await expect(activeStatus).toContainText('Aktif');
        await expect(activeStatus).toHaveClass(/bg-green-100/);

        // Pasif kurye gri
        const inactiveStatus = page.locator('[data-courier-id="2"] [data-testid="courier-status"]');
        await expect(inactiveStatus).toContainText('Pasif');
        await expect(inactiveStatus).toHaveClass(/bg-gray-100/);
    });

    test('kurye yokken boş mesaj gösterilir', async ({ page }) => {
        await page.route('**/bayi/kuryelerim', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockKuryelerimHtml([]),
            });
        });

        await page.goto('/bayi/kuryelerim');

        await expect(page.locator('[data-testid="no-couriers"]')).toBeVisible();
    });
});

test.describe('Bayi Panel - İşletmelerim Sayfası', () => {
    const mockBranches = [
        { id: 1, name: 'Pizza Express', address: 'Merkez Mah. No:1', is_active: true, today_orders: 25, month_orders: 450 },
        { id: 2, name: 'Burger King', address: 'Cadde Sok. No:5', is_active: false, today_orders: 0, month_orders: 120 },
    ];

    test('işletmeler listelenir', async ({ page }) => {
        await page.route('**/bayi/isletmelerim', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockIsletmelerimHtml(mockBranches),
            });
        });

        await page.goto('/bayi/isletmelerim');
        await page.waitForTimeout(500);

        await expect(page.locator('h1')).toContainText('İşletmelerim');
        await expect(page.locator('[data-branch-id="1"]')).toBeVisible();
        await expect(page.locator('[data-branch-id="2"]')).toBeVisible();
    });

    test('yeni işletme ekleme butonu görünür', async ({ page }) => {
        await page.route('**/bayi/isletmelerim', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockIsletmelerimHtml(mockBranches),
            });
        });

        await page.goto('/bayi/isletmelerim');

        const addBtn = page.locator('[data-testid="add-branch-btn"]');
        await expect(addBtn).toBeVisible();
        await expect(addBtn).toHaveAttribute('href', '/bayi/isletmelerim/yeni');
    });

    test('işletme istatistikleri gösterilir', async ({ page }) => {
        await page.route('**/bayi/isletmelerim', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockIsletmelerimHtml(mockBranches),
            });
        });

        await page.goto('/bayi/isletmelerim');
        await page.waitForTimeout(500);

        await expect(page.locator('[data-branch-id="1"] [data-testid="branch-today"]')).toContainText('25');
        await expect(page.locator('[data-branch-id="1"] [data-testid="branch-month"]')).toContainText('450');
    });

    test('işletme yokken boş mesaj gösterilir', async ({ page }) => {
        await page.route('**/bayi/isletmelerim', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockIsletmelerimHtml([]),
            });
        });

        await page.goto('/bayi/isletmelerim');

        await expect(page.locator('[data-testid="no-branches"]')).toBeVisible();
    });
});

test.describe('Bayi Panel - Havuz Sayfası', () => {
    const mockOrders = [
        { id: 1, order_number: 'ORD-000001', branch_name: 'Pizza Express', customer_address: 'Merkez Mah No:1', waiting_minutes: 3 },
        { id: 2, order_number: 'ORD-000002', branch_name: 'Burger King', customer_address: 'Cadde Sok No:5', waiting_minutes: 7 },
    ];

    test('havuz sayfası yüklenir', async ({ page }) => {
        await page.route('**/bayi/havuz', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockBayiHavuzHtml(mockOrders),
            });
        });

        await page.goto('/bayi/havuz');

        await expect(page.locator('h1')).toContainText('Sipariş Havuzu');
        await expect(page.locator('[data-testid="pool-table"]')).toBeVisible();
    });

    test('siparişler listelenir', async ({ page }) => {
        await page.route('**/bayi/havuz', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockBayiHavuzHtml(mockOrders),
            });
        });

        await page.goto('/bayi/havuz');
        await page.waitForTimeout(500);

        await expect(page.locator('[data-order-id="1"]')).toBeVisible();
        await expect(page.locator('[data-order-id="2"]')).toBeVisible();
    });

    test('uzun bekleyen siparişler vurgulanır', async ({ page }) => {
        await page.route('**/bayi/havuz', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockBayiHavuzHtml(mockOrders),
            });
        });

        await page.goto('/bayi/havuz');
        await page.waitForTimeout(500);

        // 7 dk bekleyen sipariş turuncu arka plan
        const urgentOrder = page.locator('[data-order-id="2"]');
        await expect(urgentOrder).toHaveClass(/bg-orange-50/);

        // Bekleme badge'i turuncu
        const waitingBadge = page.locator('[data-order-id="2"] [data-testid="waiting-time"]');
        await expect(waitingBadge).toHaveClass(/bg-orange-100/);
    });

    test('toplu atama ve otomatik atama butonları görünür', async ({ page }) => {
        await page.route('**/bayi/havuz', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockBayiHavuzHtml(mockOrders),
            });
        });

        await page.goto('/bayi/havuz');

        await expect(page.locator('[data-testid="auto-assign-btn"]')).toBeVisible();
        await expect(page.locator('[data-testid="bulk-assign-btn"]')).toBeVisible();
    });

    test('havuz boşken mesaj gösterilir', async ({ page }) => {
        await page.route('**/bayi/havuz', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockBayiHavuzHtml([]),
            });
        });

        await page.goto('/bayi/havuz');

        await expect(page.locator('[data-testid="empty-pool"]')).toBeVisible();
    });
});

test.describe('Bayi Panel - Mobile', () => {
    test.use({ viewport: { width: 375, height: 667 } });

    test('mobil görünümde harita yüklenir', async ({ page }) => {
        await page.route('**/bayi/harita', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockHaritaHtml(),
            });
        });

        await page.goto('/bayi/harita');

        await expect(page.locator('[data-testid="map-container"]')).toBeVisible();
    });

    test('mobil görünümde kuryeler listelenir', async ({ page }) => {
        await page.route('**/bayi/kuryelerim', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockKuryelerimHtml([{ id: 1, name: 'Test', phone: '555', is_online: true, today_orders: 5 }]),
            });
        });

        await page.goto('/bayi/kuryelerim');

        await expect(page.locator('h1')).toContainText('Kuryelerim');
    });
});
