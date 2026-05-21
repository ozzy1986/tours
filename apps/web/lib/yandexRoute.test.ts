import { describe, expect, it } from 'vitest'
import { mergeRouteSegmentCoordinates } from './yandexRoute'

describe('mergeRouteSegmentCoordinates', () => {
  it('concatenates segments without duplicating junction points', () => {
    const merged = mergeRouteSegmentCoordinates([
      [
        [0, 0],
        [1, 1],
        [2, 2],
      ],
      [
        [2, 2],
        [3, 3],
      ],
      [
        [3, 3],
        [4, 4],
      ],
    ])

    expect(merged).toEqual([
      [0, 0],
      [1, 1],
      [2, 2],
      [3, 3],
      [4, 4],
    ])
  })

  it('returns empty array when no segments', () => {
    expect(mergeRouteSegmentCoordinates([])).toEqual([])
  })
})
