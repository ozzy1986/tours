<script setup lang="ts">
import emblaCarouselVue from 'embla-carousel-vue'
import { Calendar, ChevronLeft, ChevronRight, Clock } from 'lucide-vue-next'
import { computed, defineAsyncComponent } from 'vue'
import { useData } from 'vike-vue/useData'
import PriceBadge from '@/components/PriceBadge.vue'

const YandexRouteMap = defineAsyncComponent({
  loader: () => import('@/components/YandexRouteMap.vue'),
  ssr: false,
})
import { formatDate, formatDuration, formatPrice } from '@/lib/format'
import type { Data } from './+data'

const { tour } = useData<Data>()

const photos = computed(() => {
  if (tour.photos?.length) return tour.photos
  if (tour.cover_url) return [{ id: 0, url: tour.cover_url, alt: tour.title, position: 0 }]
  return []
})

const [emblaRef, emblaApi] = emblaCarouselVue({ loop: photos.value.length > 1 })

const minPrice = computed(() => {
  if (!tour.departures?.length) return null
  return Math.min(...tour.departures.map((d) => d.price_cents))
})

const currency = computed(() => tour.departures[0]?.currency ?? 'RUB')

function scrollPrev() {
  emblaApi.value?.scrollPrev()
}
function scrollNext() {
  emblaApi.value?.scrollNext()
}
</script>

<template>
  <article class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <a
      href="/tours"
      class="mb-6 inline-flex items-center gap-1 text-sm font-medium text-accent hover:text-accent-hover"
    >
      <ChevronLeft class="h-4 w-4" />
      Назад к каталогу
    </a>

    <div class="grid gap-10 lg:grid-cols-2 lg:gap-12">
      <div>
        <div v-if="photos.length" class="relative">
          <div ref="emblaRef" class="overflow-hidden rounded-2xl">
            <div class="flex">
              <div
                v-for="photo in photos"
                :key="photo.id"
                class="min-w-0 flex-[0_0_100%]"
              >
                <img
                  :src="photo.url"
                  :alt="photo.alt || tour.title"
                  class="aspect-[4/3] w-full object-cover"
                />
              </div>
            </div>
          </div>
          <template v-if="photos.length > 1">
            <button
              type="button"
              class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-surface-elevated/90 p-2 shadow"
              aria-label="Предыдущее фото"
              @click="scrollPrev"
            >
              <ChevronLeft class="h-5 w-5" />
            </button>
            <button
              type="button"
              class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-surface-elevated/90 p-2 shadow"
              aria-label="Следующее фото"
              @click="scrollNext"
            >
              <ChevronRight class="h-5 w-5" />
            </button>
          </template>
        </div>
        <div
          v-else
          class="flex aspect-[4/3] items-center justify-center rounded-2xl bg-primary-muted text-muted"
        >
          Нет фотографий
        </div>

        <section class="mt-8">
          <h2 class="font-display text-lg font-bold">Маршрут на карте</h2>
          <div class="mt-4">
            <YandexRouteMap :route="tour.route_geojson" height="360px" />
          </div>
        </section>
      </div>

      <div>
        <div class="flex flex-wrap gap-2">
          <span
            v-for="cat in tour.categories"
            :key="cat.id"
            class="rounded-full bg-primary-muted px-3 py-0.5 text-xs font-semibold text-primary"
          >
            {{ cat.name }}
          </span>
        </div>

        <h1 class="mt-3 font-display text-2xl font-extrabold sm:text-4xl">
          {{ tour.title }}
        </h1>
        <p class="mt-2 text-lg text-muted">
          {{ tour.summary }}
        </p>

        <div class="mt-6 flex flex-wrap items-center gap-4">
          <span class="inline-flex items-center gap-2 text-sm font-medium">
            <Clock class="h-4 w-4 text-accent" />
            {{ formatDuration(tour.duration_days) }}
          </span>
          <PriceBadge
            v-if="minPrice != null"
            :cents="minPrice"
            :currency="currency"
          />
          <span v-else class="text-sm text-muted">Цена по запросу</span>
        </div>

        <div class="mt-8 whitespace-pre-wrap text-base leading-relaxed text-ink/90">
          {{ tour.description }}
        </div>

        <section class="mt-10">
          <h2 class="font-display text-lg font-bold">Ближайшие выезды</h2>
          <div class="mt-4 overflow-x-auto rounded-2xl border border-border">
            <table class="w-full min-w-[480px] text-left text-sm">
              <thead class="bg-surface text-xs uppercase tracking-wide text-muted">
                <tr>
                  <th class="px-4 py-3">Начало</th>
                  <th class="px-4 py-3">Окончание</th>
                  <th class="px-4 py-3">Цена</th>
                  <th class="px-4 py-3">Места</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="dep in tour.departures"
                  :key="dep.id"
                  class="border-t border-border"
                >
                  <td class="px-4 py-3">
                    <span class="inline-flex items-center gap-1">
                      <Calendar class="h-3.5 w-3.5 text-accent" />
                      {{ formatDate(dep.starts_on) }}
                    </span>
                  </td>
                  <td class="px-4 py-3">{{ formatDate(dep.ends_on) }}</td>
                  <td class="px-4 py-3 font-semibold text-primary">
                    {{ formatPrice(dep.price_cents, dep.currency) }}
                  </td>
                  <td class="px-4 py-3 text-muted">
                    {{ dep.seats_available }} / {{ dep.seats_total }}
                  </td>
                </tr>
                <tr v-if="!tour.departures?.length">
                  <td colspan="4" class="px-4 py-6 text-center text-muted">
                    Даты выездов уточняйте у организатора.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </div>
  </article>
</template>
