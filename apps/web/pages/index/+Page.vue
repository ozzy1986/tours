<script setup lang="ts">
import { Sparkles } from 'lucide-vue-next'
import { useData } from 'vike-vue/useData'
import SearchBar from '@/components/SearchBar.vue'
import TourCard from '@/components/TourCard.vue'
import type { Data } from './+data'

const { featured, categories } = useData<Data>()
</script>

<template>
  <div>
    <section
      class="relative overflow-hidden bg-gradient-to-br from-primary-muted via-surface to-accent-muted"
    >
      <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
        <div class="mx-auto max-w-3xl text-center">
          <p
            class="mb-4 inline-flex items-center gap-2 rounded-full bg-surface-elevated px-4 py-1.5 text-sm font-medium text-accent shadow-sm"
          >
            <Sparkles class="h-4 w-4" />
            Семантический поиск по описанию
          </p>
          <h1 class="font-display text-3xl font-extrabold tracking-tight text-ink sm:text-5xl">
            Найдите тур, который
            <span class="text-primary">чувствуете</span>
          </h1>
          <p class="mt-4 text-base text-muted sm:text-lg">
            Опишите желаемый отдых своими словами — подберём подходящие маршруты из каталога.
          </p>
          <div class="mt-8">
            <SearchBar large />
          </div>
        </div>
      </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
      <h2 class="font-display text-xl font-bold sm:text-2xl">Категории</h2>
      <div class="mt-4 flex flex-wrap gap-2">
        <a
          v-for="cat in categories"
          :key="cat.id"
          :href="`/tours?category=${encodeURIComponent(cat.slug)}`"
          class="btn-link rounded-full border border-border bg-surface-elevated px-4 py-2 text-sm font-medium transition hover:border-primary hover:bg-primary-muted hover:text-primary"
        >
          {{ cat.name }}
        </a>
        <a
          href="/tours"
          class="btn-link rounded-full border border-dashed border-border px-4 py-2 text-sm text-muted hover:border-accent hover:text-accent"
        >
          Все туры
        </a>
      </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
      <div class="mb-6 flex items-end justify-between gap-4">
        <div>
          <h2 class="font-display text-xl font-bold sm:text-2xl">Популярные туры</h2>
          <p class="mt-1 text-sm text-muted">Недавно опубликованные маршруты</p>
        </div>
        <a
          href="/tours"
          class="shrink-0 text-sm font-semibold text-accent hover:text-accent-hover"
        >
          Смотреть все →
        </a>
      </div>
      <div
        v-if="featured.length"
        class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
      >
        <TourCard v-for="tour in featured" :key="tour.id" :tour="tour" />
      </div>
      <p v-else class="rounded-2xl border border-dashed border-border p-8 text-center text-muted">
        Туры скоро появятся в каталоге.
      </p>
    </section>
  </div>
</template>
