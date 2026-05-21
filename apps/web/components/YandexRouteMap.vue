<script setup lang="ts">
import { computed, ref, shallowRef, watch } from 'vue'
import type { RouteFeature } from '@yandex/ymaps3-types'
import {
  YandexMap,
  YandexMapDefaultFeaturesLayer,
  YandexMapDefaultMarker,
  YandexMapDefaultSchemeLayer,
  YandexMapFeature,
  getCenterFromCoords,
  yandexMapIsLoaded,
  yandexMapLoadError,
} from 'vue-yandex-maps'
import { extractRouteWaypoints, routeCenter } from '@/lib/route'
import { fetchRoadRoute } from '@/lib/yandexRoute'
import type { RouteGeoJson } from '@/lib/types'

const props = defineProps<{
  route: RouteGeoJson | null | undefined
  height?: string
}>()

const apikey = (import.meta.env.PUBLIC_ENV__PUBLIC_YANDEX_MAPS_API_KEY ?? '').trim()
const hasKey = Boolean(apikey)

const waypoints = computed(() => extractRouteWaypoints(props.route))
const coordinates = computed(() => waypoints.value.map((wp) => wp.coordinates))
const center = computed(() => routeCenter(coordinates.value))

const roadRoute = shallowRef<RouteFeature | null>(null)
const routeLoading = ref(false)
const routeFallback = ref(false)

const mapLocation = ref({
  center: center.value,
  zoom: coordinates.value.length > 1 ? 6 : 10,
})

const mapFailed = computed(
  () => hasKey && Boolean(yandexMapLoadError.value),
)

const lineStyle = {
  stroke: [{ color: 'oklch(0.55 0.12 195)', width: 5 }],
}

const straightLineFeature = computed(() => {
  if (coordinates.value.length < 2) return null
  return {
    geometry: {
      type: 'LineString' as const,
      coordinates: coordinates.value,
    },
    style: lineStyle,
  }
})

const displayedRoute = computed(() => {
  if (roadRoute.value) {
    return {
      ...roadRoute.value,
      style: lineStyle,
    }
  }
  return straightLineFeature.value
})

function formatLoadError(err: unknown): string {
  if (err instanceof Error) return err.message
  if (typeof err === 'string') return err
  return 'Не удалось загрузить Яндекс.Карты'
}

async function loadRoadRoute() {
  const points = coordinates.value
  if (points.length < 2 || !yandexMapIsLoaded.value) {
    roadRoute.value = null
    routeFallback.value = false
    return
  }

  routeLoading.value = true
  routeFallback.value = false

  try {
    const feature = await fetchRoadRoute(points)
    roadRoute.value = feature

    if (!feature) {
      routeFallback.value = true
      mapLocation.value = {
        center: center.value,
        zoom: points.length > 1 ? 6 : 10,
      }
      return
    }

    const routeCoords = feature.geometry.coordinates
    if (routeCoords.length) {
      mapLocation.value = {
        center: getCenterFromCoords(routeCoords),
        zoom: Math.min(12, Math.max(5, 8 - Math.floor(points.length / 2))),
      }
    }
  } catch {
    roadRoute.value = null
    routeFallback.value = true
  } finally {
    routeLoading.value = false
  }
}

watch(
  [coordinates, yandexMapIsLoaded],
  () => {
    if (!yandexMapIsLoaded.value) return
    mapLocation.value.center = center.value
    void loadRoadRoute()
  },
  { immediate: true },
)
</script>

<template>
  <div
    class="relative overflow-hidden rounded-2xl border border-border bg-accent-muted/30"
    :style="{ height: height ?? '320px' }"
  >
    <p
      v-if="!hasKey"
      class="flex h-full items-center justify-center px-4 text-center text-sm text-muted"
    >
      Укажите PUBLIC_ENV__PUBLIC_YANDEX_MAPS_API_KEY в apps/web/.env. Ключ «JavaScript API и HTTP Геокодер», в Referer — localhost, сайт открывайте на http://localhost:3000.
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
        location: mapLocation,
      }"
      class="h-full w-full"
    >
      <YandexMapDefaultSchemeLayer />
      <YandexMapDefaultFeaturesLayer />
      <YandexMapDefaultMarker
        v-for="(wp, index) in waypoints"
        :key="`${wp.coordinates[0]}-${wp.coordinates[1]}-${index}`"
        :settings="{
          coordinates: wp.coordinates,
          title: wp.name,
          subtitle: index === 0 ? 'Старт' : index === waypoints.length - 1 ? 'Финиш' : `Остановка ${index}`,
        }"
      />
      <YandexMapFeature
        v-if="displayedRoute"
        :settings="displayedRoute"
      />
    </YandexMap>
    <div
      v-else
      class="flex h-full items-center justify-center text-sm text-muted"
    >
      Загрузка карты…
    </div>
    <p
      v-if="routeLoading && yandexMapIsLoaded && coordinates.length > 1"
      class="pointer-events-none absolute inset-x-0 bottom-0 bg-background/80 px-3 py-1.5 text-center text-xs text-muted"
    >
      Строим маршрут по дорогам…
    </p>
    <p
      v-else-if="routeFallback && coordinates.length > 1"
      class="pointer-events-none absolute inset-x-0 bottom-0 bg-background/80 px-3 py-1.5 text-center text-xs text-muted"
    >
      Маршрут по дорогам недоступен — прямая линия между точками. Нужен ключ Router API в кабинете Яндекса (servicesApikeys.router). Карты: Referer localhost, открывайте http://localhost:3000.
    </p>
  </div>
</template>
