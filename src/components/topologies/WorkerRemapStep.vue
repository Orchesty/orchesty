<script setup lang="ts">
import { ref, watch, computed, onMounted } from 'vue'
import { fetchWorkers } from '@/services/workersService'
import { autoMapWorkers } from '@/utils/topologyWorkerMapping'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import DropdownMenu, { type DropdownMenuSection } from '@/components/ui/DropdownMenu.vue'

interface Props {
  sourceWorkers: string[]
  modelValue: Record<string, string>
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: Record<string, string>]
  'update:valid': [value: boolean]
}>()

interface InstalledWorker {
  id: string
  name: string
}

const loading = ref(true)
const error = ref('')
const availableWorkers = ref<InstalledWorker[]>([])

const availableNames = computed(() => new Set(availableWorkers.value.map((w) => w.name)))

const isAutoMatched = (source: string, target: string): boolean =>
  target !== '' && target === source && availableNames.value.has(source)

const mappingValid = computed(() => {
  if (props.sourceWorkers.length === 0) return true
  if (availableWorkers.value.length === 0) return false
  return props.sourceWorkers.every((source) => {
    const target = props.modelValue[source]
    return typeof target === 'string' && target !== '' && availableNames.value.has(target)
  })
})

watch(mappingValid, (value) => emit('update:valid', value), { immediate: true })

const slugifyId = (value: string): string =>
  value.replace(/[^a-zA-Z0-9_-]+/g, '-').replace(/^-+|-+$/g, '') || 'worker'

const dropdownIdFor = (source: string): string => `worker-remap-${slugifyId(source)}`

const selectWorker = (source: string, workerName: string) => {
  emit('update:modelValue', { ...props.modelValue, [source]: workerName })
}

const dropdownSections = (source: string): DropdownMenuSection[] => {
  const current = props.modelValue[source] ?? ''
  return [
    {
      items: availableWorkers.value.map((worker) => {
        const isSelected = worker.name === current
        return {
          type: 'button' as const,
          label: worker.name,
          icon: isSelected
            ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-primary-600 dark:text-primary-400"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.5 7.5a1 1 0 01-1.42 0l-3.5-3.5a1 1 0 111.42-1.42l2.79 2.79 6.79-6.79a1 1 0 011.42 0z" clip-rule="evenodd" /></svg>'
            : '<span class="inline-block h-4 w-4"></span>',
          class: isSelected ? 'bg-gray-50 font-medium text-gray-900 dark:bg-gray-700 dark:text-white' : '',
          onClick: () => selectWorker(source, worker.name),
        }
      }),
    },
  ]
}

const loadWorkers = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await fetchWorkers({ page: 1, limit: 1000, sort: 'name', order: 'asc' })
    availableWorkers.value = response.data.map((w) => ({ id: w.id, name: w.name }))

    const hasUserSelection = props.sourceWorkers.some((source) => {
      const value = props.modelValue[source]
      return typeof value === 'string' && value !== ''
    })
    if (!hasUserSelection) {
      const { mapping } = autoMapWorkers(props.sourceWorkers, availableWorkers.value)
      emit('update:modelValue', mapping)
    }
  } catch (err) {
    console.error('Failed to load workers for remap step:', err)
    error.value = 'Failed to load installed workers. Please try again.'
  } finally {
    loading.value = false
  }
}

onMounted(loadWorkers)
</script>

<template>
  <div class="space-y-4">
    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
      Map the workers referenced in the topology JSON to the workers installed in this environment.
      Workers with a matching name are pre-selected automatically; the rest must be assigned manually before saving.
    </div>

    <LoadingSpinner v-if="loading" message="Loading installed workers..." />

    <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-300">
      <p class="mb-2">{{ error }}</p>
      <button type="button" class="font-medium underline" @click="loadWorkers">Retry</button>
    </div>

    <div
      v-else-if="availableWorkers.length === 0"
      class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-200"
    >
      <p class="mb-1 font-medium">No workers installed</p>
      <p>
        Add at least one worker in
        <router-link to="/settings/workers" class="font-medium underline">Settings &rarr; Workers</router-link>
        before importing this topology.
      </p>
    </div>

    <div v-else-if="sourceWorkers.length === 0" class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
      The uploaded topology does not reference any workers. You can continue.
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="source in sourceWorkers"
        :key="source"
        class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800"
      >
        <div class="flex items-center gap-2">
          <p class="min-w-0 flex-1 truncate text-sm font-medium text-gray-900 dark:text-white" :title="source">
            {{ source }}
          </p>
          <span
            v-if="isAutoMatched(source, modelValue[source] ?? '')"
            class="shrink-0 rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-medium text-green-700 dark:bg-green-900/40 dark:text-green-300"
          >
            auto-matched
          </span>
          <span
            v-else-if="!modelValue[source] || !availableNames.has(modelValue[source] ?? '')"
            class="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300"
          >
            needs mapping
          </span>
          <span
            v-else
            class="shrink-0 rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-medium text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"
          >
            remapped
          </span>
        </div>
        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">From topology JSON</p>

        <div class="mt-2">
          <DropdownMenu
            :id="dropdownIdFor(source)"
            :sections="dropdownSections(source)"
            width="w-full"
            block
          >
            <template #trigger>
              <span
                class="flex w-full items-center justify-between rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600"
                :class="{ 'text-gray-400 dark:text-gray-400': !modelValue[source] }"
              >
                <span class="truncate">{{ modelValue[source] || 'Select worker...' }}</span>
                <svg class="ms-2 h-4 w-4 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                </svg>
              </span>
            </template>
          </DropdownMenu>
        </div>
      </div>
    </div>
  </div>
</template>
