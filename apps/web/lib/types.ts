export interface Category {
  id: number
  slug: string
  name: string
  icon: string | null
  description: string | null
  position: number
}

export interface TourPhoto {
  id: number
  url: string
  alt: string | null
  position: number
}

export interface TourDeparture {
  id: number
  starts_on: string
  ends_on: string
  price_cents: number
  currency: string
  seats_total: number
  seats_available: number
}

export interface TourSummary {
  id: number
  slug: string
  title: string
  summary: string
  duration_days: number
  cover_url: string | null
  categories: Category[]
  min_price_cents: number | null | undefined
  currency: string
}

export interface RouteGeoJson {
  type: 'LineString' | 'Feature' | 'FeatureCollection'
  coordinates?: [number, number][]
  geometry?: {
    type: string
    coordinates: [number, number][]
  }
  features?: Array<{
    geometry?: {
      type: string
      coordinates: [number, number][]
    }
  }>
  waypoints?: Array<{ lat: number; lng: number; name?: string } | [number, number]>
}

export interface TourDetail {
  id: number
  slug: string
  title: string
  summary: string
  description: string
  duration_days: number
  cover_url: string | null
  meta_title: string | null
  meta_description: string | null
  route_geojson: RouteGeoJson | null
  published_at: string | null
  categories: Category[]
  photos: TourPhoto[]
  departures: TourDeparture[]
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface PaginatedTours {
  data: TourSummary[]
  meta: PaginationMeta
  links?: Record<string, string | null>
}

export interface SearchMeta {
  query: string
  mode: string
  count: number
}

export interface SearchResponse {
  data: TourSummary[]
  meta: SearchMeta
}

export type SortOption =
  | 'newest'
  | 'price_asc'
  | 'price_desc'
  | 'duration_asc'
  | 'duration_desc'

export interface TourFiltersState {
  category: string[]
  duration_min?: number
  duration_max?: number
  price_min?: number
  price_max?: number
  date_from?: string
  date_to?: string
  sort?: SortOption
  page?: number
  per_page?: number
}
