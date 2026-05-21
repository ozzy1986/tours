import type { LngLat, LngLatBounds, RouteFeature } from '@yandex/ymaps3-types'

type Ymaps3Global = {
  ready: Promise<void>
  route: (options: {
    points: LngLat[]
    type: 'driving' | 'walking' | 'transit' | 'truck'
    bounds?: boolean
  }) => Promise<Array<{ toRoute: () => RouteFeature }>>
}

function getYmaps3(): Ymaps3Global | null {
  const w = window as Window & { ymaps3?: Ymaps3Global }
  return w.ymaps3 ?? null
}

function boundsFromCoords(coordinates: LngLat[]): LngLatBounds {
  let minLng = Infinity
  let minLat = Infinity
  let maxLng = -Infinity
  let maxLat = -Infinity

  for (const [lng, lat] of coordinates) {
    minLng = Math.min(minLng, lng)
    minLat = Math.min(minLat, lat)
    maxLng = Math.max(maxLng, lng)
    maxLat = Math.max(maxLat, lat)
  }

  return [
    [minLng, minLat],
    [maxLng, maxLat],
  ]
}

/** Merge segment polylines without duplicating junction vertices. */
export function mergeRouteSegmentCoordinates(
  segments: LngLat[][],
): LngLat[] {
  const merged: LngLat[] = []

  for (let i = 0; i < segments.length; i++) {
    const segment = segments[i]
    if (!segment.length) continue

    if (i === 0) {
      merged.push(...segment)
      continue
    }

    merged.push(...segment.slice(1))
  }

  return merged
}

async function fetchRouteSegment(
  ymaps3: Ymaps3Global,
  from: LngLat,
  to: LngLat,
): Promise<LngLat[]> {
  const routes = await ymaps3.route({
    points: [from, to],
    type: 'walking',
    bounds: false,
  })

  const feature = routes[0]?.toRoute()
  if (!feature?.geometry?.coordinates?.length) {
    return []
  }

  return feature.geometry.coordinates
}

/**
 * Build a road-following route through all waypoints using Yandex Router API.
 * Requires `router` in servicesApikeys when initializing vue-yandex-maps.
 */
export async function fetchRoadRoute(
  waypoints: LngLat[],
): Promise<RouteFeature | null> {
  if (waypoints.length < 2) {
    return null
  }

  const ymaps3 = getYmaps3()
  if (!ymaps3) {
    return null
  }

  await ymaps3.ready

  if (waypoints.length === 2) {
    const routes = await ymaps3.route({
      points: waypoints,
      type: 'walking',
      bounds: true,
    })
    const feature = routes[0]?.toRoute()
    if (!feature?.geometry?.coordinates?.length) {
      return null
    }
    return feature
  }

  const segments: LngLat[][] = []
  for (let i = 0; i < waypoints.length - 1; i++) {
    const segment = await fetchRouteSegment(ymaps3, waypoints[i], waypoints[i + 1])
    if (!segment.length) {
      return null
    }
    segments.push(segment)
  }

  const coordinates = mergeRouteSegmentCoordinates(segments)
  if (coordinates.length < 2) {
    return null
  }

  const bounds = boundsFromCoords(coordinates)

  return {
    type: 'Feature',
    geometry: {
      type: 'LineString',
      coordinates,
    },
    properties: {
      bounds,
    },
  } as RouteFeature
}
