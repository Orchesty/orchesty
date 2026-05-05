<script setup lang="ts">
import { ref, computed } from 'vue'
import { Terminal, Sparkles, Link as LinkIcon } from 'lucide-vue-next'
import type { OnboardingAction } from '@/types/trace'

/**
 * OnboardingActionCard renders a single tagged block emitted by the Trace
 * onboarding_step summariser ([shell] / [prompt] / [link]) as a Bauhaus-grade
 * card with a Copy-to-Clipboard control. Mirrors the UX on the public
 * /ai-bootstrap page so users get a consistent "copy this prompt into your
 * AI editor" gesture regardless of where they encountered it.
 *
 * Link actions render an outbound anchor instead of a copy button — the
 * meaningful gesture there is "open" rather than "copy".
 */

interface Props {
  action: OnboardingAction
}

const props = defineProps<Props>()

const copied = ref(false)

const isLink = computed(() => props.action.kind === 'link')
const copyableValue = computed(() => props.action.value ?? '')

const variantLabel = computed(() => {
  switch (props.action.kind) {
    case 'shell':
      return 'Shell'
    case 'prompt':
      return 'Prompt'
    case 'link':
      return 'Link'
    default:
      return ''
  }
})

const handleCopy = async () => {
  if (isLink.value) return
  try {
    await navigator.clipboard.writeText(copyableValue.value)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (err) {
    console.error('Failed to copy onboarding action:', err)
  }
}
</script>

<template>
  <div
    class="my-3 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900 overflow-hidden"
  >
    <div
      class="flex items-center justify-between gap-2 border-b border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-800"
    >
      <div class="flex items-center gap-2 min-w-0">
        <Terminal v-if="action.kind === 'shell'" class="h-4 w-4 shrink-0 text-primary-600 dark:text-primary-500" aria-hidden="true" />
        <Sparkles v-else-if="action.kind === 'prompt'" class="h-4 w-4 shrink-0 text-primary-600 dark:text-primary-500" aria-hidden="true" />
        <LinkIcon v-else class="h-4 w-4 shrink-0 text-primary-600 dark:text-primary-500" aria-hidden="true" />
        <span class="text-[10px] font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ variantLabel }}</span>
        <span class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ action.label }}</span>
      </div>

      <button
        v-if="!isLink"
        type="button"
        :title="copied ? 'Copied!' : 'Copy to clipboard'"
        class="inline-flex shrink-0 items-center gap-1 rounded-md px-2 py-1 text-xs font-medium transition-colors focus:outline-hidden"
        :class="copied
          ? 'text-green-600 dark:text-green-400'
          : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white'"
        @click="handleCopy"
      >
        <svg v-if="!copied" class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
          <path d="M360-240q-33 0-56.5-23.5T280-320v-480q0-33 23.5-56.5T360-880h360q33 0 56.5 23.5T800-800v480q0 33-23.5 56.5T720-240H360Zm0-80h360v-480H360v480ZM200-80q-33 0-56.5-23.5T120-160v-560h80v560h440v80H200Zm160-240v-480 480Z"/>
        </svg>
        <svg v-else class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
          <path d="M382-240 154-468l57-57 171 171 367-367 57 57-424 424Z" />
        </svg>
        <span>{{ copied ? 'Copied' : 'Copy' }}</span>
      </button>
    </div>

    <div v-if="isLink" class="px-3 py-2">
      <a
        :href="action.href"
        target="_blank"
        rel="noopener noreferrer"
        class="break-all text-sm text-primary-600 hover:underline dark:text-primary-400"
      >
        {{ action.href }}
      </a>
    </div>
    <pre
      v-else
      class="m-0 max-h-80 overflow-auto whitespace-pre-wrap wrap-break-word bg-gray-900 px-3 py-2 text-xs font-mono leading-relaxed text-gray-100 dark:bg-gray-950"
    ><code>{{ action.value }}</code></pre>
  </div>
</template>
