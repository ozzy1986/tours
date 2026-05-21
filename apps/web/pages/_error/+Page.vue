<script setup lang="ts">
import { computed } from 'vue'
import { usePageContext } from 'vike-vue/usePageContext'

const pageContext = usePageContext()

const status = computed(() => pageContext.abortStatusCode ?? 500)
const message = computed(
  () =>
    (typeof pageContext.abortReason === 'string' && pageContext.abortReason) ||
    'Что-то пошло не так. Попробуйте обновить страницу.',
)

const title = computed(() => {
  if (status.value === 404) return 'Страница не найдена'
  if (status.value === 503) return 'Сервис недоступен'
  return 'Ошибка'
})
</script>

<template>
  <div class="mx-auto max-w-lg px-4 py-20 text-center sm:px-6">
    <p class="font-display text-6xl font-extrabold text-primary/30">{{ status }}</p>
    <h1 class="mt-4 font-display text-2xl font-bold text-ink">{{ title }}</h1>
    <p class="mt-3 text-muted">{{ message }}</p>
    <div class="mt-8 flex flex-wrap justify-center gap-3">
      <a
        href="/"
        class="rounded-xl bg-primary px-6 py-3 text-sm font-semibold text-white hover:bg-primary-hover"
      >
        На главную
      </a>
      <a
        href="/tours"
        class="rounded-xl border border-border px-6 py-3 text-sm font-semibold hover:border-primary hover:text-primary"
      >
        Каталог туров
      </a>
    </div>
  </div>
</template>
