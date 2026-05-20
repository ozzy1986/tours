<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import {
  YandexMap,
  YandexMapDefaultFeaturesLayer,
  YandexMapDefaultSchemeLayer,
  YandexMapFeature,
} from 'vue-yandex-maps'
import { extractRouteCoordinates, routeCenter } from '@/lib/route'
import type { RouteGeoJson } from '@/lib/types'

const props = defineProps<{
  route: RouteGeoJson | null | undefined
  height?: string
}>()

const mounted = ref(false)
onMounted(() => {
  mounted.value = true
})

const coordinates = computed(() => extractRouteCoordinates(props.route))
const center = computed(() => routeCenter(coordinates.value))
const hasKey = Boolean(import.meta.env.PUBLIC_YANDEX_MAPS_API_KEY)
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
      Укажите PUBLIC_YANDEX_MAPS_API_KEY для отображения карты маршрута.
    </p>
    <p
      v-else-if="!coordinates.length"
      class="flex h-full items-center justify-center px-4 text-center text-sm text-muted"
    >
      Маршрут для этого тура пока не добавлен.
    </p>
    <YandexMap
      v-else-if="mounted"
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
