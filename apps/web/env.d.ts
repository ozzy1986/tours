/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly PUBLIC_API_URL?: string
  readonly PUBLIC_ENV__PUBLIC_YANDEX_MAPS_API_KEY?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}

declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<object, object, unknown>
  export default component
}
