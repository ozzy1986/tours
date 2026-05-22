import type {
  Category,
  PaginatedTours,
  SearchResponse,
  TourDetail,
  TourFiltersState,
  TourSummary,
} from './types'
import { filtersToQuery } from './filters'

export const API_BASE =
  import.meta.env.PUBLIC_ENV__PUBLIC_API_URL?.replace(/\/$/, '') ||
  'http://localhost:8000'

export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
  ) {
    super(message)
    this.name = 'ApiError'
  }
}

const JSON_HEADERS = { Accept: 'application/json' } as const

async function apiFetch(url: string, init?: RequestInit): Promise<Response> {
  try {
    return await fetch(url, init)
  } catch {
    throw new ApiError(
      'Не удалось связаться с API. Запустите сервер на порту 8000 и обновите страницу.',
      503,
    )
  }
}

async function parseJson<T>(res: Response): Promise<T> {
  if (!res.ok) {
    let message = res.statusText
    try {
      const body = (await res.json()) as { message?: string }
      if (body.message) message = body.message
    } catch {
      /* empty */
    }
    throw new ApiError(message, res.status)
  }
  return res.json() as Promise<T>
}

function buildUrl(path: string, query?: Record<string, string | string[]>): string {
  const url = new URL(`${API_BASE}${path}`)
  if (query) {
    for (const [key, value] of Object.entries(query)) {
      if (Array.isArray(value)) {
        for (const v of value) url.searchParams.append(key, v)
      } else {
        url.searchParams.set(key, value)
      }
    }
  }
  return url.toString()
}

interface LaravelCollection<T> {
  data: T
}

interface LaravelPaginated<T> {
  data: T[]
  meta: PaginatedTours['meta']
  links?: PaginatedTours['links']
}

export async function fetchCategories(): Promise<Category[]> {
  const res = await fetch(buildUrl('/api/categories'), {
    headers: { Accept: 'application/json' },
  })
  const json = await parseJson<LaravelCollection<Category[]>>(res)
  return json.data
}

export async function fetchFeaturedTours(): Promise<TourSummary[]> {
  const res = await fetch(buildUrl('/api/tours/featured'), {
    headers: { Accept: 'application/json' },
  })
  const json = await parseJson<LaravelCollection<TourSummary[]>>(res)
  return json.data
}

export async function fetchTours(filters: TourFiltersState): Promise<PaginatedTours> {
  const query = filtersToQuery(filters)
  const res = await apiFetch(buildUrl('/api/tours', query), {
    headers: JSON_HEADERS,
  })
  return parseJson<LaravelPaginated<TourSummary>>(res)
}

export async function fetchTourBySlug(slug: string): Promise<TourDetail | null> {
  const res = await apiFetch(buildUrl(`/api/tours/${encodeURIComponent(slug)}`), {
    headers: JSON_HEADERS,
  })
  if (res.status === 404) return null
  const json = await parseJson<{ data?: TourDetail } & TourDetail>(res)
  return 'data' in json && json.data ? json.data : json
}

export interface SearchResult {
  tours: TourSummary[]
  meta: SearchResponse['meta'] | null
  fallback?: boolean
  message?: string
}

export async function searchTours(q: string, limit = 20): Promise<SearchResult> {
  const res = await apiFetch(buildUrl('/api/search'), {
    method: 'POST',
    headers: {
      ...JSON_HEADERS,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ q, limit }),
  })

  if (res.status === 503) {
    const body = (await res.json()) as {
      message?: string
      fallback?: TourSummary[]
      meta?: SearchResponse['meta']
    }
    return {
      tours: body.fallback ?? [],
      meta: body.meta ?? { query: q, mode: 'fallback', count: body.fallback?.length ?? 0 },
      fallback: true,
      message: body.message,
    }
  }

  const json = await parseJson<SearchResponse>(res)
  return { tours: json.data, meta: json.meta, fallback: false }
}
