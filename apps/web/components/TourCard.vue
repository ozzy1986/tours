<script setup lang="ts">
import { Calendar, MapPin } from 'lucide-vue-next'
import PriceBadge from '@/components/PriceBadge.vue'
import { formatDuration } from '@/lib/format'
import type { TourSummary } from '@/lib/types'

defineProps<{
  tour: TourSummary
}>()
</script>

<template>
  <a
    :href="`/tours/${tour.slug}`"
    class="group flex h-full flex-col overflow-hidden rounded-2xl border border-border bg-surface-elevated shadow-sm transition hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-md"
  >
    <div class="relative aspect-[4/3] overflow-hidden bg-primary-muted">
      <img
        v-if="tour.cover_url"
        :src="tour.cover_url"
        :alt="tour.title"
        class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
        loading="lazy"
      />
      <div
        v-else
        class="flex h-full items-center justify-center text-muted"
      >
        <MapPin class="h-10 w-10 opacity-40" />
      </div>
      <div
        v-if="tour.min_price_cents != null"
        class="absolute bottom-3 left-3 rounded-lg bg-surface-elevated/95 px-2.5 py-1 shadow-sm backdrop-blur"
      >
        <span class="text-xs text-muted">от</span>
        <PriceBadge :cents="tour.min_price_cents" :currency="tour.currency" size="sm" />
      </div>
    </div>

    <div class="flex flex-1 flex-col gap-2 p-4">
      <h3 class="font-display text-lg font-bold leading-snug text-ink group-hover:text-primary">
        {{ tour.title }}
      </h3>
      <p class="line-clamp-2 text-sm text-muted">
        {{ tour.summary }}
      </p>
      <div class="mt-auto flex flex-wrap items-center gap-2 pt-2">
        <span
          class="inline-flex items-center gap-1 rounded-full bg-accent-muted px-2.5 py-0.5 text-xs font-medium text-accent"
        >
          <Calendar class="h-3.5 w-3.5" />
          {{ formatDuration(tour.duration_days) }}
        </span>
        <span
          v-for="cat in tour.categories.slice(0, 2)"
          :key="cat.id"
          class="rounded-full bg-primary-muted px-2.5 py-0.5 text-xs font-medium text-primary"
        >
          {{ cat.name }}
        </span>
      </div>
    </div>
  </a>
</template>
