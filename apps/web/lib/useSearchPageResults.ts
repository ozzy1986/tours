import { ref, watch } from 'vue'
import { usePageContext } from 'vike-vue/usePageContext'
import { searchTours } from '@/lib/api'
import type { SearchMeta, TourSummary } from '@/lib/types'
import type { Data } from '../pages/search/+data'

/**
 * Keeps search results in sync with `?q=` on client-side navigations
 * (e.g. header search while already on /search).
 */
export function useSearchPageResults(initial: Data) {
  const pageContext = usePageContext()

  const query = ref(initial.query)
  const tours = ref<TourSummary[]>(initial.tours)
  const meta = ref<SearchMeta | null>(initial.meta)
  const fallback = ref(initial.fallback)
  const message = ref(initial.message)
  const loading = ref(false)

  function urlQuery(): string {
    const q = pageContext.urlParsed?.search?.q
    return (typeof q === 'string' ? q : '').trim()
  }

  async function load(q: string) {
    if (!q) {
      query.value = ''
      tours.value = []
      meta.value = null
      fallback.value = false
      message.value = undefined
      return
    }

    loading.value = true
    try {
      const result = await searchTours(q)
      query.value = q
      tours.value = result.tours
      meta.value = result.meta
      fallback.value = result.fallback ?? false
      message.value = result.message
    } finally {
      loading.value = false
    }
  }

  watch(
    () => urlQuery(),
    (q) => {
      if (q !== query.value.trim()) {
        void load(q)
      }
    },
    { immediate: true },
  )

  return { query, tours, meta, fallback, message, loading }
}
