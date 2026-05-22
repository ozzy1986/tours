import { expect, test } from '@playwright/test'

/**
 * Live admin + Ollama E2E. Requires:
 * - php artisan serve on :8000
 * - Ollama running with model from llm_settings (e.g. gemma3:4b)
 * - LLM enabled in /admin/llm-settings
 * - admin@example.com / password
 */
test.describe('Filament admin LLM tour generation', () => {
  test('generates tour draft in browser without PHP timeout', async ({ page }) => {
    test.setTimeout(720_000)

    await page.goto('/admin/login', { waitUntil: 'domcontentloaded' })
    await page.locator('input[type="email"], input#email, input[name="email"]').first().fill('admin@example.com')
    await page.locator('input[type="password"], input#password, input[name="password"]').first().fill('password')
    await page.getByRole('button', { name: /войти|sign in|log in/i }).click()
    await expect(page.getByRole('heading', { name: 'Инфопанель' })).toBeVisible({ timeout: 30_000 })

    await page.goto('/admin/tours/create', { waitUntil: 'domcontentloaded' })

    const llmButton = page.getByRole('button', { name: 'Сгенерировать через LLM' })
    await expect(llmButton).toBeEnabled({ timeout: 60_000 })
    await llmButton.click()

    await expect(page.getByRole('heading', { name: 'Генерация тура через LLM' })).toBeVisible({ timeout: 15_000 })

    await page.getByRole('textbox', { name: /Промпт/i }).fill('3 дня, Казань, городской тур, июнь')
    await page.getByRole('button', { name: 'Отправить' }).click()

    const success = page.getByText('Черновик сгенерирован')
    const error = page.getByText('Ошибка LLM')
    const fatal = page.getByText(/Maximum execution time|Internal Server Error|FatalError/i)

    await expect(success.or(error).or(fatal)).toBeVisible({ timeout: 660_000 })

    if (await fatal.isVisible().catch(() => false)) {
      throw new Error('PHP fatal / timeout in browser: ' + (await page.locator('body').innerText()).slice(0, 500))
    }
    if (await error.isVisible().catch(() => false)) {
      const body = await page.locator('.fi-no-notification, [role="status"]').allTextContents()
      throw new Error('LLM error in admin: ' + body.join(' | '))
    }

    await expect(success).toBeVisible()

    const titleInput = page.locator('input').filter({ has: page.locator('xpath=ancestor::div[contains(@class,"fi-fo-field-wrp")]//label[contains(.,"Название") or contains(.,"Title")]') }).first()
      .or(page.locator('[wire\\:model*="title"], input[id*="title"]').first())

    const titleValue = await titleInput.inputValue().catch(async () => {
      return page.locator('input[type="text"]').first().inputValue()
    })

    expect(titleValue.trim().length).toBeGreaterThan(3)
    expect(titleValue).not.toBe('Новый тур')
  })
})
