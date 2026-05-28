import { ref, computed, inject, provide, type InjectionKey, type Ref } from 'vue'
import { useRoute } from 'vue-router'

interface HelpContext {
  isOpen: Ref<boolean>
  contextHelpId: Ref<string | undefined>
  open: (helpId?: string) => void
  close: () => void
  toggle: (helpId?: string) => void
}

const HELP_KEY: InjectionKey<HelpContext> = Symbol('help')

export function provideHelp(): HelpContext {
  const isOpen = ref(false)
  const contextHelpId = ref<string | undefined>(undefined)

  function open(helpId?: string) {
    if (helpId) contextHelpId.value = helpId
    isOpen.value = true
  }

  function close() {
    isOpen.value = false
  }

  function toggle(helpId?: string) {
    if (isOpen.value) {
      close()
    } else {
      open(helpId)
    }
  }

  const ctx: HelpContext = { isOpen, contextHelpId, open, close, toggle }
  provide(HELP_KEY, ctx)
  return ctx
}

export function useHelp(): HelpContext {
  const route = useRoute()

  const fallback: HelpContext = {
    isOpen: ref(false),
    contextHelpId: ref(undefined),
    open: () => {},
    close: () => {},
    toggle: () => {},
  }

  const ctx = inject(HELP_KEY, fallback)

  const routeHelpId = computed(() => (route.meta?.helpId as string) || undefined)

  return {
    ...ctx,
    contextHelpId: computed({
      get: () => ctx.contextHelpId.value ?? routeHelpId.value,
      set: (v) => { ctx.contextHelpId.value = v },
    }),
  }
}
