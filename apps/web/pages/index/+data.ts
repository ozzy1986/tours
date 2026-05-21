import { fetchCategories, fetchFeaturedTours } from '@/lib/api'
import { rethrowPageError } from '@/lib/pageError'
import type { Category, TourSummary } from '@/lib/types'

export type Data = {
  featured: TourSummary[]
  categories: Category[]
  title: string
  description: string
}

export async function data(): Promise<Data> {
  try {
    const [featured, categories] = await Promise.all([
      fetchFeaturedTours(),
      fetchCategories(),
    ])

    return {
      featured,
      categories,
      title: 'Главная',
      description: 'Подбор туров с умным поиском, фильтрами и актуальными датами выездов.',
    }
  } catch (err) {
    rethrowPageError(err)
  }
}
