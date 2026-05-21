import { searchTours } from '@/lib/api'
import { rethrowPageError } from '@/lib/pageError'
import type { SearchMeta, TourSummary } from '@/lib/types'
import type { PageContextServer } from 'vike/types'

export type Data = {
  query: string
  tours: TourSummary[]
  meta: SearchMeta | null
  fallback: boolean
  message?: string
  title: string
  description: string
}

export async function data(pageContext: PageContextServer): Promise<Data> {
  try {
    const q = (pageContext.urlParsed.search.q as string | undefined)?.trim() ?? ''

    if (!q) {
      return {
        query: '',
        tours: [],
        meta: null,
        fallback: false,
        title: 'Поиск туров',
        description: 'Семантический поиск по описанию желаемого тура.',
      }
    }

    const { tours, meta, fallback, message } = await searchTours(q)

    return {
      query: q,
      tours,
      meta,
      fallback,
      message,
      title: `Поиск: ${q}`,
      description: `Результаты по запросу «${q}».`,
    }
  } catch (err) {
    rethrowPageError(err)
  }
}
