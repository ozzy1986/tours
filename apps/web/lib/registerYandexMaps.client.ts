import type { App } from 'vue'
import { createYmaps } from 'vue-yandex-maps'

/**
 * Yandex Maps JS API 3.0 — see quickstart:
 * https://yandex.ru/maps-api/docs/js-api/common/quickstart.html#localhost
 * - Key: «JavaScript API и HTTP Геокодер», Referer must include `localhost`
 * - Dev URL: http://localhost:3000 (not 127.0.0.1)
 * - Road routes need a Router API key in servicesApikeys.router
 */
let registered = false

export async function registerYandexMapsPlugin(app: App): Promise<void> {
  if (registered) {
    return
  }

  const apikey = (import.meta.env.PUBLIC_ENV__PUBLIC_YANDEX_MAPS_API_KEY ?? '').trim()
  if (!apikey) {
    return
  }

  // Router API is a separate Yandex pack (`Матрица расстояний и построения маршрута`),
  // so we only register it when a dedicated key is provided — otherwise the
  // call would return 401 and pollute the console.
  const routerKey = (import.meta.env.PUBLIC_ENV__PUBLIC_YANDEX_MAPS_ROUTER_KEY ?? '').trim()

  app.use(
    createYmaps({
      apikey,
      lang: 'ru_RU',
      initializeOn: 'onPluginInit',
      cdnLibraryLoading: { enabled: true },
      ...(routerKey ? { servicesApikeys: { router: routerKey } } : {}),
    }),
  )
  registered = true
}
