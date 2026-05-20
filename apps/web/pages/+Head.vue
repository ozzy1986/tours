<script setup lang="ts">
import { usePageContext } from 'vike-vue/usePageContext'

const pageContext = usePageContext()
const title = () => {
  const t = pageContext.data?.title as string | undefined
  return t ? `${t} · Tours` : 'Tours — каталог туров'
}
const description = () =>
  (pageContext.data?.description as string | undefined) ??
  'Подбор туров по России и миру. Умный поиск, фильтры и актуальные даты выездов.'
</script>

<template>
  <title>{{ title() }}</title>
  <meta name="description" :content="description()" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
</template>
