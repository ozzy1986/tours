import { defineConfig, devices } from '@playwright/test'

const port = Number(process.env.PLAYWRIGHT_PORT ?? 3000)
const host = process.env.PLAYWRIGHT_HOST ?? '127.0.0.1'
const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? `http://${host}:${port}`
const mockApiPort = Number(process.env.MOCK_API_PORT ?? 8000)
const mockApiHost = process.env.MOCK_API_HOST ?? '127.0.0.1'
const mockApiUrl = `http://${mockApiHost}:${mockApiPort}`

const webCommand = process.env.CI
  ? `npm run preview -- --host ${host} --port ${port}`
  : 'npm run dev'

export default defineConfig({
  testDir: './e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: process.env.CI ? 'github' : 'list',
  use: {
    ...devices['Desktop Chrome'],
    ...(process.env.PLAYWRIGHT_CHANNEL
      ? { channel: process.env.PLAYWRIGHT_CHANNEL as 'chrome' | 'chromium' }
      : {}),
    baseURL,
    trace: 'on-first-retry',
  },
  webServer: [
    {
      command: `node e2e/mock-api.mjs`,
      url: `${mockApiUrl}/healthz`,
      reuseExistingServer: !process.env.CI,
      timeout: 30_000,
      env: {
        MOCK_API_PORT: String(mockApiPort),
        MOCK_API_HOST: mockApiHost,
      },
    },
    {
      command: webCommand,
      url: baseURL,
      reuseExistingServer: !process.env.CI,
      timeout: 120_000,
      env: {
        PUBLIC_ENV__PUBLIC_API_URL: mockApiUrl,
      },
    },
  ],
})
