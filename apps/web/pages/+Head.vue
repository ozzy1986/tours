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
  <link rel="icon" href="/favicon.ico" sizes="any" />
  <link rel="icon" href="/favicon.svg" type="image/svg+xml" />
  <link rel="icon" href="/favicon-32x32.png" type="image/png" sizes="32x32" />
  <link rel="icon" href="/favicon-16x16.png" type="image/png" sizes="16x16" />
  <link rel="apple-touch-icon" href="/apple-touch-icon.png" />
  <meta name="theme-color" content="#E86245" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
</template>
