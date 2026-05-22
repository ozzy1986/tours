import { describe, expect, it, vi, beforeEach } from 'vitest'
import { ref, nextTick } from 'vue'
import { searchTours } from '@/lib/api'

vi.mock('vike-vue/usePageContext', () => ({
  usePageContext: () => ({
    urlParsed: { search: { q: 'строка 2' } },
  }),
}))

vi.mock('@/lib/api', () => ({
  searchTours: vi.fn(),
}))

describe('useSearchPageResults', () => {
  beforeEach(() => {
    vi.mocked(searchTours).mockReset()
    vi.mocked(searchTours).mockResolvedValue({
      tours: [{ id: 2, title: 'Tour B' } as never],
      meta: { query: 'строка 2', mode: 'semantic', count: 1 },
      fallback: false,
    })
  })

  it('refetches when URL query differs from initial SSR data', async () => {
    const { useSearchPageResults } = await import('./useSearchPageResults')
    const state = useSearchPageResults({
      query: 'строка 1',
      tours: [{ id: 1, title: 'Tour A' } as never],
      meta: { query: 'строка 1', mode: 'semantic', count: 1 },
      fallback: false,
      title: '',
      description: '',
    })

    await vi.waitFor(() => {
      expect(searchTours).toHaveBeenCalledWith('строка 2')
    })
    await nextTick()
    expect(state.query.value).toBe('строка 2')
    expect(state.tours.value[0]?.title).toBe('Tour B')
  })
})
