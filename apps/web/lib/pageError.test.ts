import { describe, expect, it } from 'vitest'
import { ApiError } from './api'
import { rethrowPageError } from './pageError'

describe('rethrowPageError', () => {
  it('throws AbortRender for server ApiError', () => {
    expect(() => rethrowPageError(new ApiError('down', 500))).toThrowError(/AbortRender/)
  })

  it('throws AbortRender for 404 ApiError', () => {
    expect(() => rethrowPageError(new ApiError('missing', 404))).toThrowError(/AbortRender/)
  })
})
