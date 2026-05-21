import { expect, test } from '@playwright/test'

const SEEDED_SLUG = 'baikal-winter'

const category = {
  id: 1,
  slug: 'nature',
  name: 'Природа',
  icon: null,
  description: null,
  position: 0,
}

const tourSummary = {
  id: 1,
  slug: SEEDED_SLUG,
  title: 'Зимний Байкал',
  summary: 'Лёд и сибирские просторы',
  duration_days: 5,
  cover_url: null,
  categories: [category],
  min_price_cents: 4500000,
  currency: 'RUB',
}

const tourDetail = {
  ...tourSummary,
  description: 'Подробное описание тура.',
  meta_title: null,
  meta_description: null,
  route_geojson: null,
  published_at: '2025-01-01T00:00:00Z',
  photos: [],
  departures: [],
}

test.beforeEach(async ({ page }) => {
  await page.route('**/*api-maps.yandex.ru/**', (route) => route.abort())
  await page.route('**/*yastatic.net/**', (route) => route.abort())
  await page.route('**/api/categories', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ data: [category] }),
    })
  })
  await page.route('**/api/tours/featured', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ data: [tourSummary] }),
    })
  })
  await page.route(/\/api\/tours(\?|$)/, async (route) => {
    if (route.request().method() !== 'GET') {
      await route.continue()
      return
    }
    const url = route.request().url()
    if (url.includes(`/api/tours/${SEEDED_SLUG}`)) {
      await route.continue()
      return
    }
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        data: [tourSummary],
        meta: { current_page: 1, last_page: 1, per_page: 12, total: 1 },
      }),
    })
  })
  await page.route(`**/api/tours/${SEEDED_SLUG}`, async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ data: tourDetail }),
    })
  })
  await page.route('**/api/search', async (route) => {
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        data: [tourSummary],
        meta: { query: 'море', mode: 'semantic', count: 1 },
      }),
    })
  })
})

test.describe('smoke', () => {
  test('home page loads', async ({ page }) => {
    await page.goto('/', { waitUntil: 'domcontentloaded' })
    await expect(page.getByRole('heading', { level: 1 })).toContainText('Найдите тур')
    await expect(page.getByRole('main').getByRole('search')).toBeVisible()
  })

  test('tours catalog loads', async ({ page }) => {
    await page.goto('/tours', { waitUntil: 'domcontentloaded' })
    await expect(page.getByRole('heading', { level: 1, name: 'Каталог туров' })).toBeVisible()
  })

  test('search with query loads', async ({ page }) => {
    await page.goto('/search?q=море', { waitUntil: 'domcontentloaded' })
    await expect(page.getByRole('heading', { level: 1, name: 'Поиск туров' })).toBeVisible()
  })

  test('tour detail loads', async ({ page }) => {
    await page.goto(`/tours/${SEEDED_SLUG}`, { waitUntil: 'domcontentloaded' })
    await expect(page.getByRole('heading', { level: 1 })).toContainText('Зимний Байкал')
  })
})
