import { defineConfig, devices } from '@playwright/test';

/**
 * SeferX Lojistik - Playwright Test Configuration
 *
 * Testler route mocking kullanarak çalışır, gerçek backend gerekmez.
 * Gerçek backend ile test için: npm run test:e2e -- --project=chromium-live
 */
export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 2 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: [['html'], ['list']],

    use: {
        baseURL: 'http://localhost:8000',
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
    },

    projects: [
        // Setup project
        {
            name: 'setup',
            testMatch: /.*\.setup\.ts/,
        },

        // Default: Mocked tests (no backend required)
        {
            name: 'chromium',
            use: {
                ...devices['Desktop Chrome'],
            },
            dependencies: ['setup'],
        },

        // Mobile viewport
        {
            name: 'mobile-chrome',
            use: {
                ...devices['Pixel 5'],
            },
            dependencies: ['setup'],
        },

        // Live tests with real backend (optional)
        {
            name: 'chromium-live',
            use: {
                ...devices['Desktop Chrome'],
            },
            dependencies: ['setup'],
        },
    ],

    // Web server is optional - tests work with route mocking
    // Uncomment to test against real backend
    // webServer: {
    //     command: 'php artisan serve --port=8000',
    //     url: 'http://localhost:8000',
    //     reuseExistingServer: !process.env.CI,
    //     timeout: 120000,
    // },
});
