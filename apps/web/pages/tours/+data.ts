import { fetchCategories, fetchTours } from '@/lib/api'
import { filtersFromSearch } from '@/lib/filters'
import type { Category, PaginatedTours, TourFiltersState } from '@/lib/types'
import type { PageContextServer } from 'vike/types'

export type Data = {
  result: PaginatedTours
  categories: Category[]
  filters: TourFiltersState
  title: string
  description: string
}

export async function data(pageContext: PageContextServer): Promise<Data> {
  const search = pageContext.urlParsed.searchAll ?? pageContext.urlParsed.search
  const filters = filtersFromSearch(search as Record<string, string | string[] | undefined>)

  const [result, categories] = await Promise.all([
    fetchTours(filters),
    fetchCategories(),
  ])

  return {
    result,
    categories,
    filters,
    title: 'Каталог туров',
    description: 'Фильтры по категории, длительности, цене и датам выезда.',
  }
}
