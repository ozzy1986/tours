<script setup lang="ts">
import { formatPrice } from '@/lib/format'

withDefaults(
  defineProps<{
    cents: number | null | undefined
    currency?: string
    size?: 'sm' | 'md'
  }>(),
  { currency: 'RUB', size: 'md' },
)
</script>

<template>
  <span
    class="inline-flex items-center font-semibold text-primary"
    :class="size === 'sm' ? 'text-sm' : 'text-base'"
  >
    {{ formatPrice(cents, currency ?? 'RUB') }}
  </span>
</template>
