<script setup lang="ts">
import { computed, reactive, watch } from 'vue'
import type { Category, SortOption, TourFiltersState } from '@/lib/types'

const props = defineProps<{
  categories: Category[]
  modelValue: TourFiltersState
}>()

const emit = defineEmits<{
  'update:modelValue': [value: TourFiltersState]
  apply: []
  reset: []
}>()

const local = reactive<TourFiltersState>({
  category: [],
  duration_min: undefined,
  duration_max: undefined,
  price_min: undefined,
  price_max: undefined,
  date_from: undefined,
  date_to: undefined,
  sort: 'newest',
  page: 1,
})

watch(
  () => props.modelValue,
  (v) => {
    Object.assign(local, {
      category: [...v.category],
      duration_min: v.duration_min,
      duration_max: v.duration_max,
      price_min: v.price_min,
      price_max: v.price_max,
      date_from: v.date_from,
      date_to: v.date_to,
      sort: v.sort ?? 'newest',
      page: v.page ?? 1,
    })
  },
  { immediate: true, deep: true },
)

const sortOptions: { value: SortOption; label: string }[] = [
  { value: 'newest', label: 'Сначала новые' },
  { value: 'price_asc', label: 'Цена ↑' },
  { value: 'price_desc', label: 'Цена ↓' },
  { value: 'duration_asc', label: 'Короткие' },
  { value: 'duration_desc', label: 'Длинные' },
]

const selectedCategorySet = computed(() => new Set(local.category))

function toggleCategory(slug: string) {
  const set = new Set(local.category)
  if (set.has(slug)) set.delete(slug)
  else set.add(slug)
  local.category = [...set]
}

function emitUpdate() {
  emit('update:modelValue', {
    ...local,
    category: [...local.category],
    page: 1,
  })
  emit('apply')
}

function onReset() {
  local.category = []
  local.duration_min = undefined
  local.duration_max = undefined
  local.price_min = undefined
  local.price_max = undefined
  local.date_from = undefined
  local.date_to = undefined
  local.sort = 'newest'
  emit('reset')
}
</script>

<template>
  <div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
      <h2 class="font-display text-lg font-bold">Фильтры</h2>
      <button
        type="button"
        class="text-sm font-medium text-accent hover:text-accent-hover"
        @click="onReset"
      >
        Сбросить
      </button>
    </div>

    <fieldset class="space-y-2">
      <legend class="mb-2 text-sm font-semibold text-ink">Категории</legend>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="cat in categories"
          :key="cat.id"
          type="button"
          class="rounded-full border px-3 py-1 text-sm transition"
          :class="
            selectedCategorySet.has(cat.slug)
              ? 'border-primary bg-primary text-white'
              : 'border-border bg-surface-elevated hover:border-primary/40'
          "
          @click="toggleCategory(cat.slug)"
        >
          {{ cat.name }}
        </button>
      </div>
    </fieldset>

    <fieldset class="grid grid-cols-2 gap-3">
      <legend class="col-span-2 mb-1 text-sm font-semibold text-ink">Длительность (дней)</legend>
      <label class="text-xs text-muted">
        От
        <input
          v-model.number="local.duration_min"
          type="number"
          min="1"
          max="60"
          class="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm"
        />
      </label>
      <label class="text-xs text-muted">
        До
        <input
          v-model.number="local.duration_max"
          type="number"
          min="1"
          max="60"
          class="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm"
        />
      </label>
    </fieldset>

    <fieldset class="grid grid-cols-2 gap-3">
      <legend class="col-span-2 mb-1 text-sm font-semibold text-ink">Цена (₽)</legend>
      <label class="text-xs text-muted">
        От
        <input
          v-model.number="local.price_min"
          type="number"
          min="0"
          class="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm"
        />
      </label>
      <label class="text-xs text-muted">
        До
        <input
          v-model.number="local.price_max"
          type="number"
          min="0"
          class="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm"
        />
      </label>
    </fieldset>

    <fieldset class="grid grid-cols-1 gap-3 sm:grid-cols-2">
      <legend class="col-span-full mb-1 text-sm font-semibold text-ink">Даты выезда</legend>
      <label class="text-xs text-muted">
        С
        <input
          v-model="local.date_from"
          type="date"
          class="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm"
        />
      </label>
      <label class="text-xs text-muted">
        По
        <input
          v-model="local.date_to"
          type="date"
          class="mt-1 w-full rounded-lg border border-border px-3 py-2 text-sm"
        />
      </label>
    </fieldset>

    <label class="block text-sm font-semibold text-ink">
      Сортировка
      <select
        v-model="local.sort"
        class="mt-2 w-full rounded-lg border border-border bg-surface-elevated px-3 py-2 text-sm"
      >
        <option v-for="opt in sortOptions" :key="opt.value" :value="opt.value">
          {{ opt.label }}
        </option>
      </select>
    </label>

    <button
      type="button"
      class="w-full rounded-xl bg-primary py-3 font-semibold text-white hover:bg-primary-hover"
      @click="emitUpdate"
    >
      Применить
    </button>
  </div>
</template>
