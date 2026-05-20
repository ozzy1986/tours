import { describe, expect, it } from 'vitest'
import { extractRouteCoordinates, routeCenter } from './route'

describe('extractRouteCoordinates', () => {
  it('reads waypoints as lng/lat objects', () => {
    const coords = extractRouteCoordinates({
      waypoints: [
        { lng: 30.3, lat: 59.9 },
        { lng: 37.6, lat: 55.7 },
      ],
    })
    expect(coords).toEqual([
      [30.3, 59.9],
      [37.6, 55.7],
    ])
  })

  it('reads LineString coordinates', () => {
    const coords = extractRouteCoordinates({
      type: 'LineString',
      coordinates: [
        [30, 60],
        [31, 61],
      ],
    })
    expect(coords).toHaveLength(2)
  })

  it('returns empty array for null', () => {
    expect(extractRouteCoordinates(null)).toEqual([])
  })
})

describe('routeCenter', () => {
  it('returns middle coordinate', () => {
    expect(
      routeCenter([
        [0, 0],
        [10, 10],
        [20, 20],
      ]),
    ).toEqual([10, 10])
  })

  it('falls back to Moscow when empty', () => {
    expect(routeCenter([])).toEqual([37.6173, 55.7558])
  })
})
