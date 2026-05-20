import type { PageContext } from 'vike/types'

export async function onCreateApp(pageContext: PageContext) {
  if (import.meta.env.SSR) {
    return
  }

  const { app } = pageContext
  const { createYmaps } = await import('vue-yandex-maps')
  const apikey = import.meta.env.PUBLIC_YANDEX_MAPS_API_KEY ?? ''

  app.use(
    createYmaps({
      apikey,
      lang: 'ru_RU',
    }),
  )
}
