import { render } from 'vike/abort'
import { fetchTourBySlug } from '@/lib/api'
import { rethrowPageError } from '@/lib/pageError'
import type { TourDetail } from '@/lib/types'
import type { PageContextServer } from 'vike/types'

export type Data = {
  tour: TourDetail
  title: string
  description: string
}

export async function data(pageContext: PageContextServer): Promise<Data> {
  try {
    const slug = pageContext.routeParams.slug as string
    const tour = await fetchTourBySlug(slug)

    if (!tour) {
      throw render(404, 'Тур не найден')
    }

    return {
      tour,
      title: tour.meta_title || tour.title,
      description: tour.meta_description || tour.summary,
    }
  } catch (err) {
    rethrowPageError(err)
  }
}
