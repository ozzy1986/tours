<script setup lang="ts">
import { Loader2, Search } from 'lucide-vue-next'
import { ref, watch } from 'vue'
import { navigate } from 'vike/client/router'

const props = withDefaults(
  defineProps<{
    modelValue?: string
    placeholder?: string
    action?: string
    large?: boolean
  }>(),
  {
    modelValue: '',
    placeholder: 'Куда хотите поехать? Опишите тур словами…',
    action: '/search',
    large: false,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
  submit: [query: string]
}>()

const local = ref(props.modelValue)
const navigating = ref(false)

watch(
  () => props.modelValue,
  (v) => {
    local.value = v
  },
)

function onInput(e: Event) {
  const v = (e.target as HTMLInputElement).value
  local.value = v
  emit('update:modelValue', v)
}

async function onSubmit() {
  const q = local.value.trim()
  if (!q || navigating.value) return
  emit('submit', q)
  navigating.value = true
  try {
    await navigate(`${props.action}?q=${encodeURIComponent(q)}`)
  } finally {
    navigating.value = false
  }
}
</script>

<template>
  <form
    class="flex w-full gap-2"
    :class="large ? 'flex-col sm:flex-row' : 'flex-row'"
    role="search"
    @submit.prevent="onSubmit"
  >
    <label class="relative min-w-0 flex-1">
      <span class="sr-only">Поиск туров</span>
      <Search
        class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-muted"
        aria-hidden="true"
      />
      <input
        :value="local"
        type="search"
        name="q"
        :placeholder="placeholder"
        aria-label="Поиск туров по описанию"
        class="w-full rounded-xl border border-border bg-surface-elevated py-3 pl-10 pr-4 text-ink shadow-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
        :class="large ? 'text-base sm:text-lg' : 'text-sm'"
        autocomplete="off"
        :disabled="navigating"
        @input="onInput"
      />
    </label>
    <button
      type="submit"
      class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-primary px-6 py-3 font-semibold text-white transition hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary/40 disabled:opacity-70"
      :class="large ? 'sm:px-8' : 'px-4 py-3 text-sm'"
      :disabled="navigating"
      :aria-busy="navigating"
    >
      <Loader2 v-if="navigating" class="h-4 w-4 animate-spin" aria-hidden="true" />
      Найти
    </button>
  </form>
</template>
