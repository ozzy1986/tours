import type { Config } from 'vike/types'
import vikeVue from 'vike-vue/config'

export default {
  extends: [vikeVue],
  prerender: false,
} satisfies Config
