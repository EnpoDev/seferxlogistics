import { test as setup } from '@playwright/test';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const courierAuthFile = path.join(__dirname, '.auth/courier.json');

/**
 * Authentication setup - Mock session oluşturur
 * Gerçek login yapmak yerine boş bir storage state kaydeder
 */
setup('setup auth storage', async ({ page }) => {
    // Boş storage state kaydet - testler route mocking kullanacak
    await page.context().storageState({ path: courierAuthFile });
});
