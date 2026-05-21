<script setup lang="ts">
import { computed } from 'vue'
import {
  YandexMap,
  YandexMapDefaultFeaturesLayer,
  YandexMapDefaultSchemeLayer,
  YandexMapFeature,
  yandexMapIsLoaded,
  yandexMapLoadError,
  yandexMapLoadStatus,
} from 'vue-yandex-maps'
import { extractRouteCoordinates, routeCenter } from '@/lib/route'
import type { RouteGeoJson } from '@/lib/types'

const props = defineProps<{
  route: RouteGeoJson | null | undefined
  height?: string
}>()

const apikey = (import.meta.env.PUBLIC_ENV__PUBLIC_YANDEX_MAPS_API_KEY ?? '').trim()
const hasKey = Boolean(apikey)

const coordinates = computed(() => extractRouteCoordinates(props.route))
const center = computed(() => routeCenter(coordinates.value))

const mapFailed = computed(
  () => hasKey && yandexMapLoadStatus.value === 'error' && Boolean(yandexMapLoadError.value),
)

function formatLoadError(err: unknown): string {
  if (err instanceof Error) return err.message
  if (typeof err === 'string') return err
  return 'Не удалось загрузить Яндекс.Карты'
}
</script>

<template>
  <div
    class="overflow-hidden rounded-2xl border border-border bg-accent-muted/30"
    :style="{ height: height ?? '320px' }"
  >
    <p
      v-if="!hasKey"
      class="flex h-full items-center justify-center px-4 text-center text-sm text-muted"
    >
      Укажите PUBLIC_ENV__PUBLIC_YANDEX_MAPS_API_KEY в apps/web/.env для отображения карты маршрута.
    </p>
    <p
      v-else-if="!coordinates.length"
      class="flex h-full items-center justify-center px-4 text-center text-sm text-muted"
    >
      Маршрут для этого тура пока не добавлен.
    </p>
    <p
      v-else-if="mapFailed"
      class="flex h-full items-center justify-center px-4 text-center text-sm text-muted"
    >
      {{ formatLoadError(yandexMapLoadError) }}
    </p>
    <YandexMap
      v-else-if="yandexMapIsLoaded"
      :settings="{
        location: {
          center: center,
          zoom: coordinates.length > 1 ? 6 : 10,
        },
      }"
      class="h-full w-full"
    >
      <YandexMapDefaultSchemeLayer />
      <YandexMapDefaultFeaturesLayer />
      <YandexMapFeature
        v-if="coordinates.length > 1"
        :settings="{
          geometry: {
            type: 'LineString',
            coordinates,
          },
          style: {
            stroke: [{ color: 'oklch(0.55 0.12 195)', width: 4 }],
          },
        }"
      />
    </YandexMap>
    <div
      v-else
      class="flex h-full items-center justify-center text-sm text-muted"
    >
      Загрузка карты…
    </div>
  </div>
</template>
