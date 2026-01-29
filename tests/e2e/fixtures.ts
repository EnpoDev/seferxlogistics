import { test as base, expect } from '@playwright/test';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

/**
 * Custom fixtures for SeferX tests
 */
export const test = base.extend<{
    courierPage: typeof base;
}>({
    // Authenticated courier context
    storageState: path.join(__dirname, '.auth/courier.json'),
});

export { expect };

/**
 * Helper: API üzerinden test verisi oluştur
 */
export async function createTestOrder(page: any, orderData?: Partial<{
    customer_name: string;
    customer_phone: string;
    customer_address: string;
    total: number;
}>) {
    const defaultData = {
        customer_name: 'Test Müşteri',
        customer_phone: '5559876543',
        customer_address: 'Test Adres Mahallesi No:1',
        total: 150.00,
        ...orderData,
    };

    // Bu fonksiyon API veya database seeding ile kullanılabilir
    return defaultData;
}

/**
 * Helper: Sayfa yüklenene kadar bekle
 */
export async function waitForPageLoad(page: any) {
    await page.waitForLoadState('networkidle');
}

/**
 * Helper: AJAX isteği tamamlanana kadar bekle
 */
export async function waitForAjax(page: any, urlPattern: string | RegExp) {
    await page.waitForResponse(
        (response: any) => {
            if (typeof urlPattern === 'string') {
                return response.url().includes(urlPattern);
            }
            return urlPattern.test(response.url());
        },
        { timeout: 10000 }
    );
}
