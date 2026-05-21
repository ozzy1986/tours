#!/usr/bin/env node
// Lightweight stand-in for the Laravel API used by Playwright smoke tests.
// Returns fixtures matching the response shapes consumed by apps/web pages.

import { createServer } from 'node:http'

const PORT = Number(process.env.MOCK_API_PORT ?? 8000)
const HOST = process.env.MOCK_API_HOST ?? '127.0.0.1'

const category = {
  id: 1,
  slug: 'nature',
  name: 'Природа',
  icon: null,
  description: null,
  position: 0,
}

const tourSummary = {
  id: 1,
  slug: 'baikal-winter',
  title: 'Зимний Байкал',
  summary: 'Лёд и сибирские просторы',
  duration_days: 5,
  cover_url: null,
  categories: [category],
  min_price_cents: 4500000,
  currency: 'RUB',
}

const tourDetail = {
  ...tourSummary,
  description: 'Подробное описание тура.',
  meta_title: null,
  meta_description: null,
  route_geojson: null,
  published_at: '2025-01-01T00:00:00Z',
  photos: [],
  departures: [],
}

function send(res, status, body) {
  res.statusCode = status
  res.setHeader('Content-Type', 'application/json; charset=utf-8')
  res.setHeader('Access-Control-Allow-Origin', '*')
  res.end(JSON.stringify(body))
}

const server = createServer((req, res) => {
  if (req.method === 'OPTIONS') {
    res.statusCode = 204
    res.setHeader('Access-Control-Allow-Origin', '*')
    res.setHeader('Access-Control-Allow-Methods', 'GET,POST,OPTIONS')
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Accept')
    return res.end()
  }

  const url = new URL(req.url ?? '/', `http://${HOST}:${PORT}`)
  const path = url.pathname

  if (path === '/api/categories') {
    return send(res, 200, { data: [category] })
  }

  if (path === '/api/tours/featured') {
    return send(res, 200, { data: [tourSummary] })
  }

  if (path === '/api/tours') {
    return send(res, 200, {
      data: [tourSummary],
      meta: { current_page: 1, last_page: 1, per_page: 12, total: 1 },
    })
  }

  const tourMatch = path.match(/^\/api\/tours\/([a-z0-9-]+)$/i)
  if (tourMatch) {
    if (tourMatch[1] === tourDetail.slug) {
      return send(res, 200, { data: tourDetail })
    }
    return send(res, 404, { message: 'Not found' })
  }

  if (path === '/api/search' && req.method === 'POST') {
    return send(res, 200, {
      data: [tourSummary],
      meta: { query: 'море', mode: 'semantic', count: 1 },
    })
  }

  if (path === '/healthz') {
    return send(res, 200, { ok: true })
  }

  return send(res, 404, { message: 'Not found' })
})

server.listen(PORT, HOST, () => {
  // eslint-disable-next-line no-console
  console.log(`[mock-api] listening on http://${HOST}:${PORT}`)
})
