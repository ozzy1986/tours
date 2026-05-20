import { describe, expect, it } from 'vitest'
import { buildToursUrl, filtersFromSearch, filtersToQuery } from './filters'

describe('filtersFromSearch', () => {
  it('parses category slugs and pagination', () => {
    const filters = filtersFromSearch({
      category: ['hiking', 'sea'],
      sort: 'price_asc',
      page: '2',
      per_page: '24',
    })

    expect(filters.category).toEqual(['hiking', 'sea'])
    expect(filters.sort).toBe('price_asc')
    expect(filters.page).toBe(2)
    expect(filters.per_page).toBe(24)
  })

  it('defaults sort and page when missing', () => {
    const filters = filtersFromSearch({})
    expect(filters.sort).toBe('newest')
    expect(filters.page).toBe(1)
    expect(filters.per_page).toBe(12)
    expect(filters.category).toEqual([])
  })
})

describe('filtersToQuery / buildToursUrl', () => {
  it('builds query string and tours path', () => {
    const url = buildToursUrl({
      category: ['hiking'],
      sort: 'price_desc',
      page: 2,
      per_page: 12,
    })

    expect(url).toContain('/tours?')
    expect(url).toContain('category=hiking')
    expect(url).toContain('sort=price_desc')
    expect(url).toContain('page=2')
  })

  it('omits default sort from query', () => {
    const q = filtersToQuery({
      category: [],
      sort: 'newest',
      page: 1,
      per_page: 12,
    })
    expect(q.sort).toBeUndefined()
    expect(buildToursUrl({ category: [], sort: 'newest', page: 1, per_page: 12 })).toBe('/tours')
  })
})
