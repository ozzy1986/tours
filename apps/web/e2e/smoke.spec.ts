import { expect, test } from '@playwright/test'

const API_BASE = process.env.PUBLIC_API_URL?.replace(/\/$/, '') || 'http://127.0.0.1:8000'
const SEEDED_SLUG = 'baikal-winter'

async function resolveTourSlug(): Promise<string> {
  try {
    const res = await fetch(`${API_BASE}/api/tours?per_page=1`)
    if (!res.ok) return SEEDED_SLUG
    const json = (await res.json()) as { data?: { slug?: string }[] }
    return json.data?.[0]?.slug ?? SEEDED_SLUG
  } catch {
    return SEEDED_SLUG
  }
}

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
    await page.route('**/*api-maps.yandex.ru/**', (route) => route.abort())
    await page.route('**/*yastatic.net/**', (route) => route.abort())
    const slug = await resolveTourSlug()
    await page.goto(`/tours/${slug}`, { waitUntil: 'domcontentloaded' })
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible()
  })
})
