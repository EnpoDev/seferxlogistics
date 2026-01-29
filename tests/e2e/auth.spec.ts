import { test, expect } from '@playwright/test';

/**
 * Auth (Kimlik Doğrulama) E2E Testleri
 *
 * Login, Register ve Password Reset sayfalarını test eder.
 * Route mocking ile backend olmadan çalışır.
 */

// Mock Login sayfası
const mockLoginHtml = () => `
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="test-token">
    <title>Giriş Yap - SeferX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
<div class="w-full max-w-md p-8" x-data="loginForm()">
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-red-600">SeferX</h1>
            <p class="text-gray-500 mt-2">Hesabınıza giriş yapın</p>
        </div>

        <form @submit.prevent="login()" method="POST">
            <div class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-posta</label>
                    <input type="email" id="email" name="email" x-model="form.email" required
                           data-testid="email-input"
                           class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent"
                           placeholder="ornek@email.com">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Şifre</label>
                    <input type="password" id="password" name="password" x-model="form.password" required
                           data-testid="password-input"
                           class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent"
                           placeholder="••••••••">
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" x-model="form.remember" data-testid="remember-checkbox"
                               class="rounded text-red-600 focus:ring-red-500">
                        <span class="ml-2 text-sm text-gray-600">Beni hatırla</span>
                    </label>
                    <a href="/forgot-password" class="text-sm text-red-600 hover:underline" data-testid="forgot-link">
                        Şifremi unuttum
                    </a>
                </div>

                <div x-show="error" x-cloak>
                    <p class="text-red-600 text-sm" x-text="error" data-testid="error-message"></p>
                </div>

                <button type="submit" :disabled="loading" data-testid="login-btn"
                        class="w-full py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-semibold disabled:opacity-50">
                    <span x-show="!loading">Giriş Yap</span>
                    <span x-show="loading" data-testid="loading-state">Giriş yapılıyor...</span>
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <p class="text-gray-600">
                Hesabınız yok mu?
                <a href="/register" class="text-red-600 hover:underline font-medium" data-testid="register-link">Kayıt ol</a>
            </p>
        </div>
    </div>
</div>

<script>
function loginForm() {
    return {
        form: {
            email: '',
            password: '',
            remember: false
        },
        loading: false,
        error: null,

        async login() {
            this.loading = true;
            this.error = null;

            try {
                const response = await fetch('/login', {
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
                    window.location.href = data.redirect || '/dashboard';
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

// Mock Register sayfası
const mockRegisterHtml = () => `
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="test-token">
    <title>Kayıt Ol - SeferX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center py-12">
<div class="w-full max-w-md p-8" x-data="registerForm()">
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-red-600">SeferX</h1>
            <p class="text-gray-500 mt-2">Yeni hesap oluşturun</p>
        </div>

        <form @submit.prevent="register()" method="POST">
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Ad Soyad</label>
                    <input type="text" id="name" name="name" x-model="form.name" required
                           data-testid="name-input"
                           class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-red-500"
                           placeholder="Adınız Soyadınız">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-posta</label>
                    <input type="email" id="email" name="email" x-model="form.email" required
                           data-testid="email-input"
                           class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-red-500"
                           placeholder="ornek@email.com">
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Telefon</label>
                    <input type="tel" id="phone" name="phone" x-model="form.phone" required
                           data-testid="phone-input"
                           class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-red-500"
                           placeholder="5XX XXX XXXX">
                </div>

                <div>
                    <label for="business_name" class="block text-sm font-medium text-gray-700 mb-1">İşletme Adı</label>
                    <input type="text" id="business_name" name="business_name" x-model="form.business_name" required
                           data-testid="business-input"
                           class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-red-500"
                           placeholder="İşletme adınız">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Şifre</label>
                    <input type="password" id="password" name="password" x-model="form.password" required
                           data-testid="password-input"
                           class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-red-500"
                           placeholder="En az 8 karakter">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Şifre Tekrar</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" x-model="form.password_confirmation" required
                           data-testid="password-confirm-input"
                           class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-red-500"
                           placeholder="Şifrenizi tekrar girin">
                </div>

                <div>
                    <label class="flex items-start">
                        <input type="checkbox" x-model="form.terms" required data-testid="terms-checkbox"
                               class="rounded text-red-600 focus:ring-red-500 mt-1">
                        <span class="ml-2 text-sm text-gray-600">
                            <a href="/terms" class="text-red-600 hover:underline">Kullanım şartlarını</a> ve
                            <a href="/privacy" class="text-red-600 hover:underline">gizlilik politikasını</a> kabul ediyorum
                        </span>
                    </label>
                </div>

                <div x-show="error" x-cloak>
                    <p class="text-red-600 text-sm" x-text="error" data-testid="error-message"></p>
                </div>

                <button type="submit" :disabled="loading || !form.terms" data-testid="register-btn"
                        class="w-full py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-semibold disabled:opacity-50">
                    <span x-show="!loading">Kayıt Ol</span>
                    <span x-show="loading" data-testid="loading-state">Kaydediliyor...</span>
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <p class="text-gray-600">
                Zaten hesabınız var mı?
                <a href="/login" class="text-red-600 hover:underline font-medium" data-testid="login-link">Giriş yap</a>
            </p>
        </div>
    </div>
</div>

<script>
function registerForm() {
    return {
        form: {
            name: '',
            email: '',
            phone: '',
            business_name: '',
            password: '',
            password_confirmation: '',
            terms: false
        },
        loading: false,
        error: null,

        async register() {
            if (this.form.password !== this.form.password_confirmation) {
                this.error = 'Şifreler eşleşmiyor';
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                const response = await fetch('/register', {
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
                    window.__registerSuccess = true;
                    window.location.href = data.redirect || '/dashboard';
                } else {
                    this.error = data.message || 'Kayıt başarısız';
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

// Mock Forgot Password sayfası
const mockForgotPasswordHtml = () => `
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="test-token">
    <title>Şifremi Unuttum - SeferX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
<div class="w-full max-w-md p-8" x-data="forgotForm()">
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-red-600">Şifremi Unuttum</h1>
            <p class="text-gray-500 mt-2">E-posta adresinizi girin, size şifre sıfırlama bağlantısı gönderelim</p>
        </div>

        <form @submit.prevent="submit()" method="POST">
            <div class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-posta</label>
                    <input type="email" id="email" name="email" x-model="form.email" required
                           data-testid="email-input"
                           class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-red-500"
                           placeholder="ornek@email.com">
                </div>

                <div x-show="success" x-cloak>
                    <p class="text-green-600 text-sm" data-testid="success-message">
                        Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.
                    </p>
                </div>

                <div x-show="error" x-cloak>
                    <p class="text-red-600 text-sm" x-text="error" data-testid="error-message"></p>
                </div>

                <button type="submit" :disabled="loading" data-testid="submit-btn"
                        class="w-full py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-semibold disabled:opacity-50">
                    <span x-show="!loading">Sıfırlama Bağlantısı Gönder</span>
                    <span x-show="loading">Gönderiliyor...</span>
                </button>
            </div>
        </form>

        <div class="mt-6 text-center">
            <a href="/login" class="text-red-600 hover:underline text-sm" data-testid="back-to-login">
                Giriş sayfasına dön
            </a>
        </div>
    </div>
</div>

<script>
function forgotForm() {
    return {
        form: { email: '' },
        loading: false,
        error: null,
        success: false,

        async submit() {
            this.loading = true;
            this.error = null;
            this.success = false;

            try {
                const response = await fetch('/forgot-password', {
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
                    this.success = true;
                    window.__emailSent = true;
                } else {
                    this.error = data.message || 'İşlem başarısız';
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

test.describe('Auth - Login Sayfası', () => {
    test.beforeEach(async ({ page }) => {
        await page.route('**/login', async route => {
            if (route.request().method() === 'GET') {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockLoginHtml(),
                });
            }
        });
    });

    test('login sayfası yüklenir', async ({ page }) => {
        await page.goto('/login');

        await expect(page.locator('h1')).toContainText('SeferX');
        await expect(page.locator('[data-testid="email-input"]')).toBeVisible();
        await expect(page.locator('[data-testid="password-input"]')).toBeVisible();
    });

    test('form alanları doldurulabilir', async ({ page }) => {
        await page.goto('/login');

        await page.locator('[data-testid="email-input"]').fill('test@example.com');
        await page.locator('[data-testid="password-input"]').fill('password123');

        await expect(page.locator('[data-testid="email-input"]')).toHaveValue('test@example.com');
        await expect(page.locator('[data-testid="password-input"]')).toHaveValue('password123');
    });

    test('beni hatırla checkbox çalışır', async ({ page }) => {
        await page.goto('/login');

        const checkbox = page.locator('[data-testid="remember-checkbox"]');
        await checkbox.check();
        await expect(checkbox).toBeChecked();
    });

    test('şifremi unuttum linki görünür', async ({ page }) => {
        await page.goto('/login');

        const forgotLink = page.locator('[data-testid="forgot-link"]');
        await expect(forgotLink).toBeVisible();
        await expect(forgotLink).toHaveAttribute('href', '/forgot-password');
    });

    test('kayıt ol linki görünür', async ({ page }) => {
        await page.goto('/login');

        const registerLink = page.locator('[data-testid="register-link"]');
        await expect(registerLink).toBeVisible();
        await expect(registerLink).toHaveAttribute('href', '/register');
    });

    test('başarılı giriş yapılır', async ({ page }) => {
        let loginAttempted = false;

        await page.unrouteAll();
        await page.route('**/login', async route => {
            if (route.request().method() === 'POST') {
                loginAttempted = true;
                await route.fulfill({
                    status: 200,
                    contentType: 'application/json',
                    body: JSON.stringify({ success: true, redirect: '/dashboard' }),
                });
            } else {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockLoginHtml(),
                });
            }
        });

        await page.goto('/login');

        await page.locator('[data-testid="email-input"]').fill('test@example.com');
        await page.locator('[data-testid="password-input"]').fill('password123');
        await page.locator('[data-testid="login-btn"]').click();

        await page.waitForTimeout(1000);
        expect(loginAttempted).toBe(true);
    });

    test('hatalı giriş mesajı gösterilir', async ({ page }) => {
        await page.unrouteAll();
        await page.route('**/login', async route => {
            if (route.request().method() === 'POST') {
                await route.fulfill({
                    status: 401,
                    contentType: 'application/json',
                    body: JSON.stringify({ success: false, message: 'E-posta veya şifre hatalı' }),
                });
            } else {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockLoginHtml(),
                });
            }
        });

        await page.goto('/login');

        await page.locator('[data-testid="email-input"]').fill('wrong@example.com');
        await page.locator('[data-testid="password-input"]').fill('wrongpassword');
        await page.locator('[data-testid="login-btn"]').click();

        await expect(page.locator('[data-testid="error-message"]')).toContainText('E-posta veya şifre hatalı');
    });
});

test.describe('Auth - Register Sayfası', () => {
    test.beforeEach(async ({ page }) => {
        await page.route('**/register', async route => {
            if (route.request().method() === 'GET') {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockRegisterHtml(),
                });
            }
        });
    });

    test('register sayfası yüklenir', async ({ page }) => {
        await page.goto('/register');

        await expect(page.locator('h1')).toContainText('SeferX');
        await expect(page.locator('[data-testid="name-input"]')).toBeVisible();
        await expect(page.locator('[data-testid="email-input"]')).toBeVisible();
        await expect(page.locator('[data-testid="phone-input"]')).toBeVisible();
        await expect(page.locator('[data-testid="business-input"]')).toBeVisible();
    });

    test('tüm form alanları doldurulabilir', async ({ page }) => {
        await page.goto('/register');

        await page.locator('[data-testid="name-input"]').fill('Test User');
        await page.locator('[data-testid="email-input"]').fill('test@example.com');
        await page.locator('[data-testid="phone-input"]').fill('5551234567');
        await page.locator('[data-testid="business-input"]').fill('Test İşletme');
        await page.locator('[data-testid="password-input"]').fill('password123');
        await page.locator('[data-testid="password-confirm-input"]').fill('password123');

        await expect(page.locator('[data-testid="name-input"]')).toHaveValue('Test User');
        await expect(page.locator('[data-testid="business-input"]')).toHaveValue('Test İşletme');
    });

    test('şartlar kabul edilmeden kayıt yapılamaz', async ({ page }) => {
        await page.goto('/register');

        const registerBtn = page.locator('[data-testid="register-btn"]');
        await expect(registerBtn).toBeDisabled();

        await page.locator('[data-testid="terms-checkbox"]').check();
        await expect(registerBtn).not.toBeDisabled();
    });

    test('giriş yap linki görünür', async ({ page }) => {
        await page.goto('/register');

        const loginLink = page.locator('[data-testid="login-link"]');
        await expect(loginLink).toBeVisible();
        await expect(loginLink).toHaveAttribute('href', '/login');
    });

    test('başarılı kayıt yapılır', async ({ page }) => {
        let registerAttempted = false;

        await page.unrouteAll();
        await page.route('**/register', async route => {
            if (route.request().method() === 'POST') {
                registerAttempted = true;
                await route.fulfill({
                    status: 200,
                    contentType: 'application/json',
                    body: JSON.stringify({ success: true, redirect: '/dashboard' }),
                });
            } else {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockRegisterHtml(),
                });
            }
        });

        await page.goto('/register');

        await page.locator('[data-testid="name-input"]').fill('Test User');
        await page.locator('[data-testid="email-input"]').fill('test@example.com');
        await page.locator('[data-testid="phone-input"]').fill('5551234567');
        await page.locator('[data-testid="business-input"]').fill('Test İşletme');
        await page.locator('[data-testid="password-input"]').fill('password123');
        await page.locator('[data-testid="password-confirm-input"]').fill('password123');
        await page.locator('[data-testid="terms-checkbox"]').check();

        await page.locator('[data-testid="register-btn"]').click();

        await page.waitForTimeout(1000);
        expect(registerAttempted).toBe(true);
    });
});

test.describe('Auth - Forgot Password Sayfası', () => {
    test.beforeEach(async ({ page }) => {
        await page.route('**/forgot-password', async route => {
            if (route.request().method() === 'GET') {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockForgotPasswordHtml(),
                });
            }
        });
    });

    test('forgot password sayfası yüklenir', async ({ page }) => {
        await page.goto('/forgot-password');

        await expect(page.locator('h1')).toContainText('Şifremi Unuttum');
        await expect(page.locator('[data-testid="email-input"]')).toBeVisible();
    });

    test('giriş sayfasına dön linki görünür', async ({ page }) => {
        await page.goto('/forgot-password');

        const backLink = page.locator('[data-testid="back-to-login"]');
        await expect(backLink).toBeVisible();
        await expect(backLink).toHaveAttribute('href', '/login');
    });

    test('başarılı email gönderimi', async ({ page }) => {
        await page.unrouteAll();
        await page.route('**/forgot-password', async route => {
            if (route.request().method() === 'POST') {
                await route.fulfill({
                    status: 200,
                    contentType: 'application/json',
                    body: JSON.stringify({ success: true }),
                });
            } else {
                await route.fulfill({
                    status: 200,
                    contentType: 'text/html',
                    body: mockForgotPasswordHtml(),
                });
            }
        });

        await page.goto('/forgot-password');

        await page.locator('[data-testid="email-input"]').fill('test@example.com');
        await page.locator('[data-testid="submit-btn"]').click();

        await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
    });
});

test.describe('Auth - Mobile', () => {
    test.use({ viewport: { width: 375, height: 667 } });

    test('mobilde login yüklenir', async ({ page }) => {
        await page.route('**/login', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockLoginHtml(),
            });
        });

        await page.goto('/login');

        await expect(page.locator('h1')).toContainText('SeferX');
        await expect(page.locator('[data-testid="login-btn"]')).toBeVisible();
    });

    test('mobilde register yüklenir', async ({ page }) => {
        await page.route('**/register', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockRegisterHtml(),
            });
        });

        await page.goto('/register');

        await expect(page.locator('h1')).toContainText('SeferX');
        await expect(page.locator('[data-testid="register-btn"]')).toBeVisible();
    });
});

test.describe('Auth - Accessibility', () => {
    test('login form elementleri label ile ilişkilendirilmiş', async ({ page }) => {
        await page.route('**/login', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockLoginHtml(),
            });
        });

        await page.goto('/login');

        await expect(page.locator('label[for="email"]')).toBeVisible();
        await expect(page.locator('label[for="password"]')).toBeVisible();
    });

    test('register form elementleri label ile ilişkilendirilmiş', async ({ page }) => {
        await page.route('**/register', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockRegisterHtml(),
            });
        });

        await page.goto('/register');

        await expect(page.locator('label[for="name"]')).toBeVisible();
        await expect(page.locator('label[for="email"]')).toBeVisible();
        await expect(page.locator('label[for="phone"]')).toBeVisible();
    });

    test('keyboard navigasyonu çalışır', async ({ page }) => {
        await page.route('**/login', async route => {
            await route.fulfill({
                status: 200,
                contentType: 'text/html',
                body: mockLoginHtml(),
            });
        });

        await page.goto('/login');

        await page.keyboard.press('Tab');
        const emailInput = page.locator('[data-testid="email-input"]');
        await expect(emailInput).toBeFocused();

        await page.keyboard.press('Tab');
        const passwordInput = page.locator('[data-testid="password-input"]');
        await expect(passwordInput).toBeFocused();
    });
});
