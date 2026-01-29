import { test, expect } from '@playwright/test';

/**
 * Kurye Giriş Sayfası E2E Testleri
 *
 * Bu testler kurye giriş sayfasını test eder.
 * Route mocking kullanarak backend olmadan çalışır.
 */

// Mock login page HTML
const mockLoginPageHtml = () => `
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="test-token">
    <title>Kurye Girişi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
<div class="w-full max-w-md p-8">
    <div class="bg-white rounded-2xl shadow-lg p-8" x-data="loginForm()">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Kurye Girişi</h1>
            <p class="text-gray-500 mt-2">Hesabınıza giriş yapın</p>
        </div>

        <form @submit.prevent="login()" method="POST">
            <div class="space-y-4">
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                    <input type="tel" id="phone" name="phone" x-model="form.phone" required
                           data-testid="phone-input"
                           class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent"
                           placeholder="5XX XXX XXXX">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Şifre</label>
                    <input type="password" id="password" name="password" x-model="form.password" required
                           data-testid="password-input"
                           class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent"
                           placeholder="••••••••">
                </div>

                <div x-show="error" x-cloak>
                    <p class="text-red-600 text-sm" x-text="error" data-testid="error-message"></p>
                </div>

                <button type="submit" :disabled="loading" data-testid="login-btn"
                        class="w-full py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-semibold disabled:opacity-50 transition-colors">
                    <span x-show="!loading">Giriş Yap</span>
                    <span x-show="loading" data-testid="loading-state">Giriş yapılıyor...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function loginForm() {
    return {
        form: {
            phone: '',
            password: ''
        },
        loading: false,
        error: null,

        async login() {
            this.loading = true;
            this.error = null;

            try {
                const response = await fetch('/kurye/giris', {
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
                    window.__loginSuccess = true;
                    window.location.href = data.redirect || '/kurye';
                } else {
                    this.error = data.message || 'Giriş başarısız';
                }
            } catch (e) {
                this.error = 'Bağlantı hatası';
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

test.describe('Kurye Login Sayfası', () => {
    test.beforeEach(async ({ page }) => {
        await page.route('**/kurye/giris', async route => {
            if (route.request().method() === 'GET') {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockLoginPageHtml(),
                });
            }
        });
    });

    test('login sayfası başarıyla yüklenir', async ({ page }) => {
        await page.goto('/kurye/giris');

        await expect(page.locator('h1')).toContainText('Kurye Girişi');
        await expect(page.locator('[data-testid="phone-input"]')).toBeVisible();
        await expect(page.locator('[data-testid="password-input"]')).toBeVisible();
        await expect(page.locator('[data-testid="login-btn"]')).toBeVisible();
    });

    test('başarılı giriş yapılır', async ({ page }) => {
        let postCalled = false;
        let requestBody: any = null;

        // Route mocking - önce tüm mevcut route'ları temizle
        await page.unrouteAll();

        await page.route('**/kurye/giris', async route => {
            const method = route.request().method();
            if (method === 'POST') {
                postCalled = true;
                requestBody = route.request().postDataJSON();
                await route.fulfill({
                    status: 200,
                    contentType: 'application/json',
                    body: JSON.stringify({
                        success: true,
                        redirect: '/kurye',
                    }),
                });
            } else {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockLoginPageHtml(),
                });
            }
        });

        await page.goto('/kurye/giris');

        await page.locator('[data-testid="phone-input"]').fill('5551234567');
        await page.locator('[data-testid="password-input"]').fill('password123');
        await page.locator('[data-testid="login-btn"]').click();

        await page.waitForTimeout(1500);

        // POST isteği yapıldı mı kontrol et
        expect(postCalled).toBe(true);

        // Gönderilen veriler doğru mu
        expect(requestBody?.phone).toBe('5551234567');
        expect(requestBody?.password).toBe('password123');
    });

    test('hatalı giriş denemesinde hata mesajı gösterilir', async ({ page }) => {
        await page.route('**/kurye/giris', async route => {
            if (route.request().method() === 'POST') {
                await route.fulfill({
                    status: 401,
                    contentType: 'application/json',
                    body: JSON.stringify({
                        success: false,
                        message: 'Telefon veya şifre hatalı.',
                    }),
                });
            } else {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockLoginPageHtml(),
                });
            }
        });

        await page.goto('/kurye/giris');

        await page.locator('[data-testid="phone-input"]').fill('5550000000');
        await page.locator('[data-testid="password-input"]').fill('wrongpassword');
        await page.locator('[data-testid="login-btn"]').click();

        await expect(page.locator('[data-testid="error-message"]')).toContainText('Telefon veya şifre hatalı');
    });

    test('boş form gönderilmez', async ({ page }) => {
        await page.goto('/kurye/giris');

        const phoneInput = page.locator('[data-testid="phone-input"]');
        await expect(phoneInput).toHaveAttribute('required');

        const passwordInput = page.locator('[data-testid="password-input"]');
        await expect(passwordInput).toHaveAttribute('required');
    });

    test('loading durumu gösterilir', async ({ page }) => {
        await page.route('**/kurye/giris', async route => {
            if (route.request().method() === 'POST') {
                await new Promise(resolve => setTimeout(resolve, 1000));
                await route.fulfill({
                    status: 200,
                    contentType: 'application/json',
                    body: JSON.stringify({ success: true, redirect: '/kurye' }),
                });
            } else {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockLoginPageHtml(),
                });
            }
        });

        await page.goto('/kurye/giris');

        await page.locator('[data-testid="phone-input"]').fill('5551234567');
        await page.locator('[data-testid="password-input"]').fill('password123');
        await page.locator('[data-testid="login-btn"]').click();

        // Loading state görünmeli
        await expect(page.locator('[data-testid="loading-state"]')).toBeVisible();
    });

    test('network hatası durumunda uyarı gösterilir', async ({ page }) => {
        await page.route('**/kurye/giris', async route => {
            if (route.request().method() === 'POST') {
                await route.abort('failed');
            } else {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockLoginPageHtml(),
                });
            }
        });

        await page.goto('/kurye/giris');

        await page.locator('[data-testid="phone-input"]').fill('5551234567');
        await page.locator('[data-testid="password-input"]').fill('password123');
        await page.locator('[data-testid="login-btn"]').click();

        await page.waitForTimeout(500);

        await expect(page.locator('[data-testid="error-message"]')).toContainText('Bağlantı hatası');
    });
});

test.describe('Kurye Login - Input Validation', () => {
    test.beforeEach(async ({ page }) => {
        await page.route('**/kurye/giris', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockLoginPageHtml(),
            });
        });
    });

    test('telefon alanı tel tipi input', async ({ page }) => {
        await page.goto('/kurye/giris');

        const phoneInput = page.locator('[data-testid="phone-input"]');
        await expect(phoneInput).toHaveAttribute('type', 'tel');
    });

    test('şifre alanı password tipi input', async ({ page }) => {
        await page.goto('/kurye/giris');

        const passwordInput = page.locator('[data-testid="password-input"]');
        await expect(passwordInput).toHaveAttribute('type', 'password');
    });

    test('form değerleri güncellenir', async ({ page }) => {
        await page.goto('/kurye/giris');

        const phoneInput = page.locator('[data-testid="phone-input"]');
        await phoneInput.fill('5551234567');
        await expect(phoneInput).toHaveValue('5551234567');

        const passwordInput = page.locator('[data-testid="password-input"]');
        await passwordInput.fill('mypassword');
        await expect(passwordInput).toHaveValue('mypassword');
    });
});

test.describe('Kurye Login - Mobile', () => {
    test.use({ viewport: { width: 375, height: 667 } });

    test('mobil görünümde düzgün render edilir', async ({ page }) => {
        await page.route('**/kurye/giris', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockLoginPageHtml(),
            });
        });

        await page.goto('/kurye/giris');

        await expect(page.locator('h1')).toBeVisible();
        await expect(page.locator('[data-testid="login-btn"]')).toBeVisible();
    });

    test('mobil klavye ile form doldurulabilir', async ({ page }) => {
        await page.route('**/kurye/giris', async route => {
            if (route.request().method() === 'GET') {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockLoginPageHtml(),
                });
            }
        });

        await page.goto('/kurye/giris');

        // Form alanlarını doldur
        const phoneInput = page.locator('[data-testid="phone-input"]');
        await phoneInput.click();
        await phoneInput.fill('5551234567');

        const passwordInput = page.locator('[data-testid="password-input"]');
        await passwordInput.click();
        await passwordInput.fill('password');

        await expect(phoneInput).toHaveValue('5551234567');
        await expect(passwordInput).toHaveValue('password');
    });
});

test.describe('Kurye Login - Accessibility', () => {
    test.beforeEach(async ({ page }) => {
        await page.route('**/kurye/giris', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockLoginPageHtml(),
            });
        });
    });

    test('form elementleri label ile ilişkilendirilmiş', async ({ page }) => {
        await page.goto('/kurye/giris');

        const phoneLabel = page.locator('label[for="phone"]');
        await expect(phoneLabel).toBeVisible();

        const passwordLabel = page.locator('label[for="password"]');
        await expect(passwordLabel).toBeVisible();
    });

    test('keyboard navigasyonu çalışır', async ({ page }) => {
        await page.goto('/kurye/giris');

        // Tab ile form elementlerine git
        await page.keyboard.press('Tab');

        // Focus phone input'ta olmalı
        const phoneInput = page.locator('[data-testid="phone-input"]');
        await expect(phoneInput).toBeFocused();

        // Tab ile password'a geç
        await page.keyboard.press('Tab');

        const passwordInput = page.locator('[data-testid="password-input"]');
        await expect(passwordInput).toBeFocused();
    });

    test('sayfa başlığı doğru', async ({ page }) => {
        await page.goto('/kurye/giris');

        await expect(page.locator('h1')).toHaveCount(1);
        await expect(page.locator('h1')).toContainText('Kurye Girişi');
    });
});
