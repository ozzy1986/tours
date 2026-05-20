<script setup lang="ts">
import { AlertCircle, Search } from 'lucide-vue-next'
import { useData } from 'vike-vue/useData'
import SearchBar from '@/components/SearchBar.vue'
import TourCard from '@/components/TourCard.vue'
import type { Data } from './+data'

const { query, tours, meta, fallback, message } = useData<Data>()
</script>

<template>
  <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="font-display text-2xl font-extrabold sm:text-3xl">Поиск туров</h1>
    <p class="mt-1 text-muted">Опишите желаемый отдых — подберём подходящие маршруты</p>

    <div class="mt-6 max-w-2xl">
      <SearchBar :model-value="query" large />
    </div>

    <div
      v-if="fallback && message"
      class="mt-6 flex gap-3 rounded-xl border border-accent/30 bg-accent-muted px-4 py-3 text-sm text-ink"
      role="status"
    >
      <AlertCircle class="h-5 w-5 shrink-0 text-accent" />
      <p>{{ message }}</p>
    </div>

    <template v-if="query">
      <p class="mt-8 text-sm text-muted">
        <Search class="mr-1 inline h-4 w-4" />
        Запрос: <strong class="text-ink">«{{ query }}»</strong>
        <span v-if="meta"> — {{ meta.count }} результатов</span>
        <span v-if="meta?.mode" class="text-accent"> ({{ meta.mode }})</span>
      </p>

      <div
        v-if="tours.length"
        class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
      >
        <TourCard v-for="tour in tours" :key="tour.id" :tour="tour" />
      </div>
      <p
        v-else
        class="mt-8 rounded-2xl border border-dashed border-border p-12 text-center text-muted"
      >
        По этому запросу ничего не найдено. Попробуйте описать тур иначе или
        <a href="/tours" class="font-medium text-accent hover:underline">откройте каталог</a>.
      </p>
    </template>

    <p
      v-else
      class="mt-12 rounded-2xl border border-dashed border-border p-12 text-center text-muted"
    >
      Введите запрос в строке поиска выше.
    </p>
  </div>
</template>
