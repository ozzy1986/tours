import { defineConfig, devices } from '@playwright/test'

/**
 * Smoke e2e: Vike dev server on :3000, Laravel API on :8000 (see PUBLIC_API_URL).
 * Start API first: `cd apps/api && php artisan serve --port=8000`
 * Web is started via webServer below, or reuse an existing `npm run dev`.
 */
export default defineConfig({
  testDir: './e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: process.env.CI ? 'github' : 'list',
  use: {
    ...devices['Desktop Chrome'],
    // Use system Chrome when `npx playwright install` is blocked (e.g. regional CDN).
    channel: process.env.PLAYWRIGHT_CHANNEL ?? 'chrome',
    baseURL: 'http://localhost:3000',
    trace: 'on-first-retry',
  },
  webServer: {
    command: 'npm run dev',
    url: 'http://localhost:3000',
    reuseExistingServer: !process.env.CI,
    timeout: 120_000,
  },
})
