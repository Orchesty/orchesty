<script setup lang="ts">
import { ref, watch } from 'vue'
import {
  Sparkles,
  Wrench,
  Workflow,
  Bot,
  X,
  ArrowRight,
} from 'lucide-vue-next'

interface Props {
  modelValue: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'open-trace': []
  'dismiss': []
}>()

// Local copy so we can drive an enter/leave transition independent of the
// parent's prop. The parent toggles `modelValue`; we mirror it here and
// let Vue's <Transition> animate. We also expose `update:modelValue` so
// pressing ESC or clicking the backdrop closes the modal cleanly.
const visible = ref(props.modelValue)

watch(
  () => props.modelValue,
  (value) => {
    visible.value = value
  },
)

const close = () => {
  visible.value = false
  emit('update:modelValue', false)
  emit('dismiss')
}

const handleOpenTrace = () => {
  visible.value = false
  emit('update:modelValue', false)
  emit('open-trace')
}

const onBackdropClick = (event: MouseEvent) => {
  if (event.target === event.currentTarget) {
    close()
  }
}

const onKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    close()
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="visible"
        class="fixed inset-0 z-80 flex items-center justify-center overflow-y-auto bg-gray-900/60 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="welcome-onboarding-title"
        tabindex="-1"
        @click="onBackdropClick"
        @keydown="onKeydown"
      >
        <Transition
          appear
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
        >
          <div
            v-if="visible"
            class="relative w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800"
          >
            <!-- Close X -->
            <button
              type="button"
              class="absolute right-4 top-4 z-10 inline-flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 focus:outline-hidden dark:hover:bg-gray-800 dark:hover:text-gray-200"
              :aria-label="'Close welcome modal'"
              @click="close"
            >
              <X class="h-5 w-5" />
            </button>

            <!-- Hero band -->
            <div
              class="relative overflow-hidden bg-linear-to-br from-primary-50 via-indigo-50 to-white px-8 pb-10 pt-10 dark:from-primary-950/40 dark:via-indigo-950/30 dark:to-gray-900 md:px-12 md:pt-14"
            >
              <div
                class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-primary-200/40 blur-3xl dark:bg-primary-500/10"
              />
              <div
                class="pointer-events-none absolute -bottom-20 -left-12 h-48 w-48 rounded-full bg-indigo-200/40 blur-3xl dark:bg-indigo-500/10"
              />

              <div class="relative flex items-start gap-5">
                <div
                  class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white shadow-lg ring-1 ring-primary-100 dark:bg-gray-900 dark:ring-primary-900/40"
                >
                  <Sparkles class="h-7 w-7 text-primary-600 dark:text-primary-400" />
                </div>
                <div class="min-w-0">
                  <p
                    class="text-xs font-semibold uppercase tracking-[0.2em] text-primary-600 dark:text-primary-400"
                  >
                    Welcome
                  </p>
                  <h2
                    id="welcome-onboarding-title"
                    class="mt-2 text-2xl font-bold text-gray-900 dark:text-white md:text-3xl"
                  >
                    Your Orchesty instance is ready.
                  </h2>
                  <p
                    class="mt-3 max-w-2xl text-base leading-relaxed text-gray-600 dark:text-gray-300"
                  >
                    Build a worker, design topologies, and let Orchesty run them — at your pace.
                    The next few steps will walk you from an empty instance to a working integration.
                  </p>
                </div>
              </div>
            </div>

            <!-- Feature grid -->
            <div class="grid gap-4 px-8 pb-2 pt-8 md:grid-cols-3 md:px-12">
              <div
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs transition-colors dark:border-gray-800 dark:bg-gray-900/40"
              >
                <div
                  class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-950/50 dark:text-primary-400"
                >
                  <Wrench class="h-5 w-5" />
                </div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                  Build a worker
                </h3>
                <p class="mt-1.5 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                  Use the Node.js SDK to host your custom logic — connectors, batches and mapping nodes.
                </p>
              </div>

              <div
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs transition-colors dark:border-gray-800 dark:bg-gray-900/40"
              >
                <div
                  class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400"
                >
                  <Sparkles class="h-5 w-5" />
                </div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                  Bootstrap with AI
                </h3>
                <p class="mt-1.5 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                  Bring your favourite AI editor and let it scaffold the worker from a single prompt.
                </p>
              </div>

              <div
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs transition-colors dark:border-gray-800 dark:bg-gray-900/40"
              >
                <div
                  class="mb-3 inline-flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400"
                >
                  <Workflow class="h-5 w-5" />
                </div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                  Run topologies
                </h3>
                <p class="mt-1.5 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                  Wire nodes into topologies in the editor and let Orchesty execute them safely.
                </p>
              </div>
            </div>

            <!-- Trace callout -->
            <div class="px-8 pb-2 pt-6 md:px-12">
              <div
                class="flex items-start gap-4 rounded-xl border border-primary-100 bg-primary-50/60 p-5 dark:border-primary-900/40 dark:bg-primary-950/30"
              >
                <div
                  class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white text-primary-600 ring-1 ring-primary-100 dark:bg-gray-900 dark:text-primary-400 dark:ring-primary-900/40"
                >
                  <Bot class="h-5 w-5" />
                </div>
                <div class="min-w-0">
                  <p class="text-sm font-semibold text-gray-900 dark:text-white">
                    Trace is here to help.
                  </p>
                  <p class="mt-1 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                    Open the assistant and we'll walk you through the onboarding step by step —
                    from cloning the starter to running your first topology. Ask anything along the way.
                  </p>
                </div>
              </div>
            </div>

            <!-- Footer actions -->
            <div
              class="flex flex-col-reverse items-stretch gap-3 px-8 py-7 md:flex-row md:items-center md:justify-end md:px-12"
            >
              <button
                type="button"
                class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 focus:outline-hidden focus:ring-4 focus:ring-gray-100 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 dark:focus:ring-gray-800"
                @click="close"
              >
                Maybe later
              </button>
              <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-xs transition-colors hover:bg-primary-700 focus:outline-hidden focus:ring-4 focus:ring-primary-200 dark:focus:ring-primary-900"
                @click="handleOpenTrace"
              >
                Open Trace and start onboarding
                <ArrowRight class="h-4 w-4" />
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>
