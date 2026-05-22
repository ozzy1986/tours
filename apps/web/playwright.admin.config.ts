import { defineConfig, devices } from '@playwright/test'

const adminUrl = process.env.ADMIN_URL ?? 'http://localhost:8000'

export default defineConfig({
  testDir: './e2e',
  testMatch: 'admin-llm.spec.ts',
  timeout: 720_000,
  expect: { timeout: 660_000 },
  fullyParallel: false,
  workers: 1,
  retries: 0,
  reporter: 'list',
  use: {
    ...devices['Desktop Chrome'],
    channel: 'chrome',
    baseURL: adminUrl,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
})
