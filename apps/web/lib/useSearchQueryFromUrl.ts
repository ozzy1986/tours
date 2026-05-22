import { computed } from 'vue'
import { usePageContext } from 'vike-vue/usePageContext'

/** Current `?q=` when the active route is `/search`, otherwise empty. */
export function useSearchQueryFromUrl() {
  const pageContext = usePageContext()

  return computed(() => {
    const pathname = pageContext.urlPathname as string | undefined
    if (pathname !== '/search') {
      return ''
    }
    const q = pageContext.urlParsed?.search?.q
    return typeof q === 'string' ? q : ''
  })
}
