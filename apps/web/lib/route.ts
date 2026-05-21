import type { RouteGeoJson } from './types'

export interface RouteWaypoint {
  coordinates: [number, number]
  name: string
}

/** Extract [lng, lat] pairs from API route_geojson. */
export function extractRouteCoordinates(
  geojson: RouteGeoJson | null | undefined,
): [number, number][] {
  if (!geojson) return []

  if (geojson.waypoints?.length) {
    return geojson.waypoints.map((wp) => {
      if (Array.isArray(wp)) return [wp[0], wp[1]] as [number, number]
      return [wp.lng, wp.lat]
    })
  }

  if (geojson.type === 'LineString' && geojson.coordinates?.length) {
    return geojson.coordinates
  }

  if (geojson.geometry?.coordinates?.length) {
    return geojson.geometry.coordinates
  }

  if (geojson.type === 'Feature' && geojson.geometry?.coordinates) {
    return geojson.geometry.coordinates
  }

  if (geojson.type === 'FeatureCollection' && geojson.features?.length) {
    for (const feature of geojson.features) {
      const coords = feature.geometry?.coordinates
      if (coords?.length) return coords
    }
  }

  return []
}

/** Waypoints with optional labels for map markers. */
export function extractRouteWaypoints(
  geojson: RouteGeoJson | null | undefined,
): RouteWaypoint[] {
  if (!geojson?.waypoints?.length) {
    return extractRouteCoordinates(geojson).map((coordinates, index) => ({
      coordinates,
      name: `Точка ${index + 1}`,
    }))
  }

  return geojson.waypoints
    .map((wp, index) => {
      if (Array.isArray(wp)) {
        return {
          coordinates: [wp[0], wp[1]] as [number, number],
          name: `Точка ${index + 1}`,
        }
      }
      return {
        coordinates: [wp.lng, wp.lat] as [number, number],
        name: wp.name?.trim() || `Точка ${index + 1}`,
      }
    })
    .filter((wp) => wp.coordinates[0] !== 0 || wp.coordinates[1] !== 0)
}

export function routeCenter(coords: [number, number][]): [number, number] {
  if (!coords.length) return [37.6173, 55.7558]
  const mid = Math.floor(coords.length / 2)
  return coords[mid]
}
