import { chromium } from 'playwright'

const urls = [
  'http://localhost:3000/tours/crimea-sevastopol',
  'http://localhost:3000/tours/baikal-winter',
]

const mapLoadingPattern = /Загрузка карты/
const ymapsErrorPattern = /ymaps|jsdelivr|YandexMapException|vue-yandex-maps|api-maps\.yandex/i

function redact(text) {
  return text.replace(/apikey=[^&\s]+/gi, 'apikey=REDACTED')
}

function isMapRelatedError(text) {
  return ymapsErrorPattern.test(text)
}

for (const url of urls) {
  const browser = await chromium.launch({
    headless: true,
    args: [
      '--disable-web-security',
      '--disable-features=OpaqueResponseBlocking,CrossOriginOpenerPolicy,CrossOriginEmbedderPolicy',
    ],
  })
  const page = await browser.newPage({
    extraHTTPHeaders: { Referer: 'http://localhost:3000/' },
  })
  const consoleErrors = []

  page.on('console', (msg) => {
    if (msg.type() === 'error') {
      const text = msg.text()
      consoleErrors.push(text === 'Event' && msg.location() ? `${text} @ ${msg.location().url}` : text)
    }
  })
  page.on('requestfailed', (req) => {
    const u = req.url()
    if (/yandex|jsdelivr|ymaps/i.test(u)) {
      consoleErrors.push(redact(`REQUEST_FAILED: ${u} ${req.failure()?.errorText ?? ''}`))
    }
  })
  page.on('pageerror', (err) => {
    consoleErrors.push(err.message)
  })

  console.log(`\n=== ${url} ===`)
  await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 30000 })

  const section = page.locator('section').filter({ has: page.getByRole('heading', { name: 'Маршрут на карте' }) })
  await section.waitFor({ timeout: 15000 })

  const deadline = Date.now() + 30000
  let mapText = ''
  let stuckOnLoading = true

  while (Date.now() < deadline) {
    mapText = (await section.innerText()).trim()
    if (!mapLoadingPattern.test(mapText)) {
      stuckOnLoading = false
      break
    }
    await page.waitForTimeout(500)
  }

  const mapErrors = consoleErrors.filter(isMapRelatedError).map(redact)
  const canvas = section.locator('canvas')
  const hasCanvas = (await canvas.count()) > 0

  console.log('STUCK_ON_LOADING:', stuckOnLoading)
  console.log('MAP_SECTION_PREVIEW:', mapText.slice(0, 280).replace(/\s+/g, ' '))
  console.log('HAS_MAP_CANVAS:', hasCanvas)
  console.log('MAP_RELATED_CONSOLE_ERRORS:', JSON.stringify(mapErrors, null, 2))
  console.log('ALL_CONSOLE_ERRORS:', JSON.stringify(consoleErrors.map(redact), null, 2))
  const keyHint = await page.locator('text=PUBLIC_ENV__PUBLIC_YANDEX_MAPS_API_KEY').count()
  console.log('SHOWS_MISSING_KEY_HINT:', keyHint > 0)
  const routeMissing = await page.locator('text=Маршрут для этого тура пока не добавлен').count()
  console.log('SHOWS_NO_ROUTE:', routeMissing > 0)

  const mapErrorUi = await section.locator('text=/Не удалось|загрузить|ошибк/i').count()

  if (stuckOnLoading && mapErrorUi === 0) {
    console.log('RESULT: FAIL (still loading)')
  } else if (mapErrorUi > 0) {
    console.log('RESULT: FAIL (error UI)')
  } else if (hasCanvas) {
    console.log('RESULT: PASS (map canvas visible)')
  } else if (!stuckOnLoading) {
    console.log('RESULT: PASS (left loading state)')
  } else {
    console.log('RESULT: FAIL')
  }

  if ((stuckOnLoading && mapErrorUi === 0 && !hasCanvas) || mapErrors.length > 0) {
    await page.screenshot({ path: `scripts/browser-yandex-map-${url.split('/').pop()}.png`, fullPage: false })
    process.exitCode = 1
  }

  await browser.close()
}
