import { nextTick, onUnmounted, watch, type Ref } from 'vue'

const FOCUSABLE =
  'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'

export function useDialogTrap(
  open: Ref<boolean>,
  panelRef: Ref<HTMLElement | null>,
  onClose: () => void,
) {
  let previousFocus: HTMLElement | null = null

  function handleKeydown(e: KeyboardEvent) {
    if (!open.value) return

    if (e.key === 'Escape') {
      e.preventDefault()
      onClose()
      return
    }

    if (e.key !== 'Tab' || !panelRef.value) return

    const nodes = [...panelRef.value.querySelectorAll<HTMLElement>(FOCUSABLE)].filter(
      (el) => el.offsetParent !== null,
    )
    if (!nodes.length) return

    const first = nodes[0]
    const last = nodes[nodes.length - 1]

    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault()
      last.focus()
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault()
      first.focus()
    }
  }

  watch(open, async (isOpen) => {
    if (typeof document === 'undefined') return

    if (isOpen) {
      previousFocus = document.activeElement as HTMLElement | null
      document.addEventListener('keydown', handleKeydown)
      await nextTick()
      const panel = panelRef.value
      const first = panel?.querySelector<HTMLElement>(FOCUSABLE)
      first?.focus()
      return
    }

    document.removeEventListener('keydown', handleKeydown)
    previousFocus?.focus()
    previousFocus = null
  })

  onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown)
  })
}
