import type { SortOption, TourFiltersState } from './types'

const SORT_VALUES: SortOption[] = [
  'newest',
  'price_asc',
  'price_desc',
  'duration_asc',
  'duration_desc',
]

function parseIntParam(value: string | string[] | undefined): number | undefined {
  const raw = Array.isArray(value) ? value[0] : value
  if (!raw) return undefined
  const n = Number.parseInt(raw, 10)
  return Number.isFinite(n) ? n : undefined
}

function parseStringParam(value: string | string[] | undefined): string | undefined {
  const raw = Array.isArray(value) ? value[0] : value
  return raw?.trim() || undefined
}

function parseSort(value: string | string[] | undefined): SortOption | undefined {
  const raw = parseStringParam(value)
  if (raw && SORT_VALUES.includes(raw as SortOption)) return raw as SortOption
  return undefined
}

export function filtersFromSearch(
  search: Record<string, string | string[] | undefined>,
): TourFiltersState {
  const categoryRaw = search.category
  const categories = categoryRaw
    ? (Array.isArray(categoryRaw) ? categoryRaw : [categoryRaw]).filter(Boolean)
    : []

  return {
    category: categories,
    duration_min: parseIntParam(search.duration_min),
    duration_max: parseIntParam(search.duration_max),
    price_min: parseIntParam(search.price_min),
    price_max: parseIntParam(search.price_max),
    date_from: parseStringParam(search.date_from),
    date_to: parseStringParam(search.date_to),
    sort: parseSort(search.sort) ?? 'newest',
    page: parseIntParam(search.page) ?? 1,
    per_page: parseIntParam(search.per_page) ?? 12,
  }
}

export function filtersToQuery(filters: TourFiltersState): Record<string, string | string[]> {
  const q: Record<string, string | string[]> = {}

  if (filters.category.length) q.category = filters.category
  if (filters.duration_min != null) q.duration_min = String(filters.duration_min)
  if (filters.duration_max != null) q.duration_max = String(filters.duration_max)
  if (filters.price_min != null) q.price_min = String(filters.price_min)
  if (filters.price_max != null) q.price_max = String(filters.price_max)
  if (filters.date_from) q.date_from = filters.date_from
  if (filters.date_to) q.date_to = filters.date_to
  if (filters.sort && filters.sort !== 'newest') q.sort = filters.sort
  if (filters.page && filters.page > 1) q.page = String(filters.page)
  if (filters.per_page && filters.per_page !== 12) q.per_page = String(filters.per_page)

  return q
}

export function buildToursUrl(filters: TourFiltersState): string {
  const params = new URLSearchParams()
  const q = filtersToQuery(filters)
  for (const [key, value] of Object.entries(q)) {
    if (Array.isArray(value)) {
      for (const v of value) params.append(key, v)
    } else {
      params.set(key, value)
    }
  }
  const qs = params.toString()
  return qs ? `/tours?${qs}` : '/tours'
}
