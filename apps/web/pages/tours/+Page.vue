<script setup lang="ts">
import { Filter, X } from 'lucide-vue-next'
import { onMounted, ref, watch } from 'vue'
import { navigate } from 'vike/client/router'
import { useData } from 'vike-vue/useData'
import TourCard from '@/components/TourCard.vue'
import TourFilters from '@/components/TourFilters.vue'
import { buildToursUrl } from '@/lib/filters'
import type { TourFiltersState } from '@/lib/types'
import type { Data } from './+data'

const data = useData<Data>()

const filters = ref<TourFiltersState>({
  ...data.filters,
  category: [...data.filters.category],
})
const sheetOpen = ref(false)
const teleportReady = ref(false)

onMounted(() => {
  teleportReady.value = true
})

watch(
  () => data.filters,
  (next) => {
    filters.value = { ...next, category: [...next.category] }
  },
  { deep: true },
)

async function applyFilters(next?: TourFiltersState) {
  if (next) filters.value = next
  sheetOpen.value = false
  await navigate(buildToursUrl(filters.value))
}

async function resetFilters() {
  filters.value = {
    category: [],
    sort: 'newest',
    page: 1,
    per_page: 12,
  }
  await navigate('/tours')
}

async function goToPage(page: number) {
  filters.value = { ...filters.value, page }
  await navigate(buildToursUrl(filters.value))
}
</script>

<template>
  <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-8">
      <h1 class="font-display text-2xl font-extrabold sm:text-3xl">Каталог туров</h1>
      <p class="mt-1 text-muted">
        {{ data.result.meta.total }} {{ data.result.meta.total === 1 ? 'тур' : 'туров' }}
      </p>
    </div>

    <div class="flex gap-8">
      <aside class="hidden w-72 shrink-0 lg:block">
        <div class="sticky top-24 rounded-2xl border border-border bg-surface-elevated p-5 shadow-sm">
          <TourFilters
            v-model="filters"
            :categories="data.categories"
            @apply="applyFilters()"
            @reset="resetFilters"
          />
        </div>
      </aside>

      <div class="min-w-0 flex-1">
        <button
          type="button"
          class="mb-4 flex w-full items-center justify-center gap-2 rounded-xl border border-border bg-surface-elevated py-3 text-sm font-semibold lg:hidden"
          @click="sheetOpen = true"
        >
          <Filter class="h-4 w-4" />
          Фильтры
        </button>

        <div
          v-if="data.result.data.length"
          class="grid gap-6 sm:grid-cols-2 xl:grid-cols-2"
        >
          <TourCard v-for="tour in data.result.data" :key="tour.id" :tour="tour" />
        </div>
        <p
          v-else
          class="rounded-2xl border border-dashed border-border p-12 text-center text-muted"
        >
          По выбранным фильтрам туров нет. Попробуйте изменить условия.
        </p>

        <nav
          v-if="data.result.meta.last_page > 1"
          class="mt-10 flex flex-wrap items-center justify-center gap-2"
          aria-label="Пагинация"
        >
          <button
            type="button"
            class="rounded-lg border border-border px-3 py-2 text-sm transition hover:border-primary hover:text-primary disabled:opacity-40 disabled:hover:border-border disabled:hover:text-inherit"
            :disabled="data.result.meta.current_page <= 1"
            @click="goToPage(data.result.meta.current_page - 1)"
          >
            Назад
          </button>
          <span class="px-3 text-sm text-muted">
            {{ data.result.meta.current_page }} / {{ data.result.meta.last_page }}
          </span>
          <button
            type="button"
            class="rounded-lg border border-border px-3 py-2 text-sm transition hover:border-primary hover:text-primary disabled:opacity-40 disabled:hover:border-border disabled:hover:text-inherit"
            :disabled="data.result.meta.current_page >= data.result.meta.last_page"
            @click="goToPage(data.result.meta.current_page + 1)"
          >
            Вперёд
          </button>
        </nav>
      </div>
    </div>

    <Teleport v-if="teleportReady" to="body">
      <div
        v-if="sheetOpen"
        class="fixed inset-0 z-50 lg:hidden"
        role="dialog"
        aria-modal="true"
      >
        <div
          class="absolute inset-0 cursor-pointer bg-ink/40"
          @click="sheetOpen = false"
        />
        <div
          class="absolute inset-y-0 right-0 flex w-full max-w-sm flex-col bg-surface-elevated shadow-xl"
        >
          <div class="flex items-center justify-between border-b border-border px-4 py-3">
            <span class="font-display font-bold">Фильтры</span>
            <button type="button" class="rounded-lg p-2" aria-label="Закрыть" @click="sheetOpen = false">
              <X class="h-5 w-5" />
            </button>
          </div>
          <div class="flex-1 overflow-y-auto p-5">
            <TourFilters
              v-model="filters"
              :categories="data.categories"
              @apply="applyFilters()"
              @reset="resetFilters"
            />
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
