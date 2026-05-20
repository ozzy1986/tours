<script setup lang="ts">
import type { Component } from 'vue'
import { computed, onMounted, ref, shallowRef } from 'vue'
import { extractRouteCoordinates, routeCenter } from '@/lib/route'
import type { RouteGeoJson } from '@/lib/types'

const props = defineProps<{
  route: RouteGeoJson | null | undefined
  height?: string
}>()

type YmapsComponents = {
  YandexMap: Component
  YandexMapDefaultSchemeLayer: Component
  YandexMapDefaultFeaturesLayer: Component
  YandexMapFeature: Component
}

const apikey = (import.meta.env.PUBLIC_ENV__PUBLIC_YANDEX_MAPS_API_KEY ?? '').trim()
const hasKey = Boolean(apikey)

const ymaps = shallowRef<YmapsComponents | null>(null)
const ready = ref(false)

onMounted(async () => {
  if (!hasKey) return

  try {
    const mod = await import('vue-yandex-maps')
    mod.createYmapsOptions({ apikey, lang: 'ru_RU' }, true)
    await mod.initYmaps()
    ymaps.value = {
      YandexMap: mod.YandexMap,
      YandexMapDefaultSchemeLayer: mod.YandexMapDefaultSchemeLayer,
      YandexMapDefaultFeaturesLayer: mod.YandexMapDefaultFeaturesLayer,
      YandexMapFeature: mod.YandexMapFeature,
    }
    ready.value = true
  } catch {
    ready.value = false
  }
})

const coordinates = computed(() => extractRouteCoordinates(props.route))
const center = computed(() => routeCenter(coordinates.value))
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
      Укажите PUBLIC_ENV__PUBLIC_YANDEX_MAPS_API_KEY для отображения карты маршрута.
    </p>
    <p
      v-else-if="!coordinates.length"
      class="flex h-full items-center justify-center px-4 text-center text-sm text-muted"
    >
      Маршрут для этого тура пока не добавлен.
    </p>
    <component
      v-else-if="ready && ymaps"
      :is="ymaps.YandexMap"
      :settings="{
        location: {
          center: center,
          zoom: coordinates.length > 1 ? 6 : 10,
        },
      }"
      class="h-full w-full"
    >
      <component :is="ymaps.YandexMapDefaultSchemeLayer" />
      <component :is="ymaps.YandexMapDefaultFeaturesLayer" />
      <component
        v-if="coordinates.length > 1"
        :is="ymaps.YandexMapFeature"
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
    </component>
    <div
      v-else
      class="flex h-full items-center justify-center text-sm text-muted"
    >
      Загрузка карты…
    </div>
  </div>
</template>
