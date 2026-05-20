<script setup lang="ts">
import { Search } from 'lucide-vue-next'
import { ref, watch } from 'vue'

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

function onSubmit() {
  const q = local.value.trim()
  if (!q) return
  emit('submit', q)
  if (typeof window !== 'undefined') {
    window.location.href = `${props.action}?q=${encodeURIComponent(q)}`
  }
}
</script>

<template>
  <form
    class="flex w-full gap-2"
    :class="large ? 'flex-col sm:flex-row' : 'flex-row'"
    @submit.prevent="onSubmit"
  >
    <label class="relative min-w-0 flex-1">
      <Search
        class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-muted"
        aria-hidden="true"
      />
      <input
        :value="local"
        type="search"
        name="q"
        :placeholder="placeholder"
        class="w-full rounded-xl border border-border bg-surface-elevated py-3 pl-10 pr-4 text-ink shadow-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
        :class="large ? 'text-base sm:text-lg' : 'text-sm'"
        autocomplete="off"
        @input="onInput"
      />
    </label>
    <button
      type="submit"
      class="shrink-0 rounded-xl bg-primary px-6 py-3 font-semibold text-white transition hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary/40"
      :class="large ? 'sm:px-8' : 'px-4 py-3 text-sm'"
    >
      Найти
    </button>
  </form>
</template>
