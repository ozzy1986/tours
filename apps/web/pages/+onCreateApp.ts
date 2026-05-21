import type { PageContext } from 'vike/types'

export async function onCreateApp(pageContext: PageContext) {
  if (import.meta.env.SSR) {
    return
  }

  const { registerYandexMapsPlugin } = await import('@/lib/registerYandexMaps.client')
  await registerYandexMapsPlugin(pageContext.app)
}
