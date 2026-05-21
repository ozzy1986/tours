import { render } from 'vike/abort'
import { ApiError } from './api'

function isAbortRender(err: unknown): boolean {
  return (
    typeof err === 'object' &&
    err !== null &&
    'abortStatusCode' in err &&
    typeof (err as { abortStatusCode?: unknown }).abortStatusCode === 'number'
  )
}

/** Maps API/network failures to Vike `render()` so users see +error instead of a raw 500. */
export function rethrowPageError(err: unknown): never {
  if (isAbortRender(err)) {
    throw err
  }

  if (err instanceof ApiError) {
    const status =
      err.status === 404 ? 404 : err.status >= 500 || err.status === 0 ? 503 : err.status
    throw render(status, err.message)
  }

  throw render(
    503,
    'Сервис временно недоступен. Проверьте, что API запущен, и обновите страницу.',
  )
}
