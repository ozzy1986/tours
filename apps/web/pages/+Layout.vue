<script setup lang="ts">
import { Menu, X } from 'lucide-vue-next'
import { ref } from 'vue'
import SearchBar from '@/components/SearchBar.vue'
import '@/assets/main.css'

const mobileNavOpen = ref(false)

const navLinks = [
  { href: '/', label: 'Главная' },
  { href: '/tours', label: 'Туры' },
]
</script>

<template>
  <div class="flex min-h-screen flex-col">
    <header class="sticky top-0 z-40 border-b border-border bg-surface-elevated/95 backdrop-blur">
      <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <a href="/" class="font-display text-xl font-extrabold tracking-tight text-primary">
          Tours
        </a>

        <nav class="hidden items-center gap-6 md:flex" aria-label="Основная навигация">
          <a
            v-for="link in navLinks"
            :key="link.href"
            :href="link.href"
            class="text-sm font-medium text-ink/80 transition hover:text-primary"
          >
            {{ link.label }}
          </a>
        </nav>

        <div class="hidden min-w-0 flex-1 md:block md:max-w-md lg:max-w-lg">
          <SearchBar />
        </div>

        <button
          type="button"
          class="ml-auto rounded-lg p-2 text-ink md:hidden"
          aria-label="Меню"
          @click="mobileNavOpen = !mobileNavOpen"
        >
          <X v-if="mobileNavOpen" class="h-6 w-6" />
          <Menu v-else class="h-6 w-6" />
        </button>
      </div>

      <div v-if="mobileNavOpen" class="border-t border-border px-4 py-4 md:hidden">
        <nav class="mb-4 flex flex-col gap-2">
          <a
            v-for="link in navLinks"
            :key="link.href"
            :href="link.href"
            class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-primary-muted"
            @click="mobileNavOpen = false"
          >
            {{ link.label }}
          </a>
        </nav>
        <SearchBar />
      </div>
    </header>

    <main class="flex-1">
      <slot />
    </main>

    <footer class="mt-auto border-t border-border bg-ink text-surface">
      <div
        class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-10 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8"
      >
        <div>
          <p class="font-display text-lg font-bold text-primary">Tours</p>
          <p class="mt-1 max-w-sm text-sm text-surface/70">
            Каталог авторских туров с умным поиском и актуальными датами выездов.
          </p>
        </div>
        <nav class="flex flex-wrap gap-4 text-sm">
          <a href="/tours" class="hover:text-primary">Все туры</a>
          <a href="/search?q=горы" class="hover:text-primary">Поиск</a>
        </nav>
        <p class="text-xs text-surface/50">© {{ new Date().getFullYear() }} Tours</p>
      </div>
    </footer>
  </div>
</template>
