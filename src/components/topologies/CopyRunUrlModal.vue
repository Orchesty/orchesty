<script setup lang="ts">
import { computed } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import CopyValue from '@/components/ui/CopyValue.vue'
import type { RunUrl, RunUrlMode } from '@/utils/startingPointUrls'

interface Props {
  modelValue: boolean
  nodeName: string
  urls: RunUrl[]
}

const props = defineProps<Props>()

defineEmits<{
  'update:modelValue': [value: boolean]
}>()

interface Group {
  mode: RunUrlMode
  title: string
  description: string
  urls: RunUrl[]
}

const GROUPS: Omit<Group, 'urls'>[] = [
  {
    mode: 'async',
    title: 'Async',
    description: 'Starts the process and returns its correlation_id right away.',
  },
  {
    mode: 'sync',
    title: 'Sync',
    description:
      'Waits for the Response node and returns its payload. An error on the path to the Response node is returned as 422; if no result comes in time, it answers 202 with the correlation_id.',
  },
]

const groups = computed<Group[]>(() =>
  GROUPS.map((group) => ({ ...group, urls: props.urls.filter((u) => u.mode === group.mode) })).filter(
    (group) => group.urls.length > 0,
  ),
)

const hasSync = computed(() => props.urls.some((u) => u.mode === 'sync'))

const versionLabel = (url: RunUrl): string => (url.version === 'strict' ? 'Strict version' : 'Active version')
const versionHint = (url: RunUrl): string =>
  url.version === 'strict'
    ? 'Pinned to this exact topology version (uses IDs)'
    : 'Resolves to the currently active topology version'
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="copy-run-url-modal"
    :title="`Copy URL: ${nodeName}`"
    size="2xl"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div class="space-y-5">
      <section v-for="group in groups" :key="group.mode" :data-testid="`run-url-group-${group.mode}`">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ group.title }}</h3>
        <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">{{ group.description }}</p>
        <ul class="space-y-2">
          <li
            v-for="url in group.urls"
            :key="url.url"
            class="rounded-lg border border-gray-200 px-3 py-2 dark:border-gray-700"
          >
            <div class="text-xs font-medium text-gray-700 dark:text-gray-300" :title="versionHint(url)">
              {{ versionLabel(url) }}
            </div>
            <CopyValue :value="url.url" title="Copy URL">
              <code class="break-all text-xs text-gray-900 dark:text-gray-100">{{ url.url }}</code>
            </CopyValue>
          </li>
        </ul>
      </section>

      <p v-if="!hasSync" class="text-xs text-gray-500 dark:text-gray-400" data-testid="run-url-sync-hint">
        Sync URLs are available when a Response node is reachable from this node.
      </p>
    </div>
  </Modal>
</template>
