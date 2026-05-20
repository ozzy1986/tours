export function formatPrice(cents: number | null | undefined, currency = 'RUB'): string {
  if (cents == null) return 'Цена по запросу'
  const amount = cents / 100
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency,
    maximumFractionDigits: 0,
  }).format(amount)
}

export function formatDuration(days: number): string {
  const n = Math.abs(days) % 100
  const n1 = n % 10
  if (n > 10 && n < 20) return `${days} дней`
  if (n1 > 1 && n1 < 5) return `${days} дня`
  if (n1 === 1) return `${days} день`
  return `${days} дней`
}

export function formatDate(iso: string): string {
  return new Intl.DateTimeFormat('ru-RU', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  }).format(new Date(iso))
}
