import type { App } from 'vue'
import { createYmaps } from 'vue-yandex-maps'

let registered = false

export async function registerYandexMapsPlugin(app: App): Promise<void> {
  if (registered) {
    return
  }

  const apikey = (import.meta.env.PUBLIC_ENV__PUBLIC_YANDEX_MAPS_API_KEY ?? '').trim()
  if (!apikey) {
    return
  }

  app.use(
    createYmaps({
      apikey,
      lang: 'ru_RU',
      initializeOn: 'onPluginInit',
      cdnLibraryLoading: { enabled: false },
    }),
  )
  registered = true
}
