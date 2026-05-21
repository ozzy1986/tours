import type { PageContext } from 'vike/types'

export async function onCreateApp(pageContext: PageContext) {
  if (import.meta.env.SSR) {
    return
  }

  const apikey = (import.meta.env.PUBLIC_ENV__PUBLIC_YANDEX_MAPS_API_KEY ?? '').trim()
  if (!apikey) {
    return
  }

  const { createYmaps } = await import('vue-yandex-maps')
  const { app } = pageContext

  app.use(
    createYmaps({
      apikey,
      lang: 'ru_RU',
      initializeOn: 'onPluginInit',
      cdnLibraryLoading: { enabled: false },
    }),
  )
}
