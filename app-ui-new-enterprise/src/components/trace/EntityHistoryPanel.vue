<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { Search } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import EntityRunCard from '@/components/trace/EntityRunCard.vue'
import { fetchAuditEntities } from '@/services/auditEntitiesService'
import { fetchEntityHistory } from '@/services/traceService'
import type { AuditEntity } from '@/types/settings'
import type { EntityHistoryResponse } from '@/types/trace'

const entities = ref<AuditEntity[]>([])
const loadingEntities = ref(false)

const selectedEntityId = ref<string>('')
const selectedAttribute = ref<string>('')
const identifierValue = ref<string>('')

const loadingHistory = ref(false)
const error = ref<string | null>(null)
const result = ref<EntityHistoryResponse | null>(null)

const selectedEntity = computed<AuditEntity | undefined>(() =>
  entities.value.find(e => e.id === selectedEntityId.value),
)

const attributes = computed(() => selectedEntity.value?.attributes ?? [])

watch(selectedEntityId, () => {
  // Reset dependent fields when entity changes; default to first attribute.
  selectedAttribute.value = attributes.value[0]?.name ?? ''
  identifierValue.value = ''
  result.value = null
  error.value = null
})

onMounted(async () => {
  loadingEntities.value = true
  try {
    entities.value = await fetchAuditEntities()
  } catch (e) {
    console.error('Failed to load audit entities', e)
  } finally {
    loadingEntities.value = false
  }
})

const canSubmit = computed(() =>
  Boolean(selectedEntity.value && selectedAttribute.value && identifierValue.value.trim()),
)

// Mirrors auditEntitiesService.generateKey() so the audit `key` we send to
// `/mcp/run` matches the value the backend stored when the entity was created.
const slugifyEntityKey = (name: string): string =>
  name
    .toLowerCase()
    .trim()
    .replace(/[^\w\s-]/g, '')
    .replace(/[\s_]+/g, '-')
    .replace(/^-+|-+$/g, '')

const handleSubmit = async () => {
  if (!canSubmit.value || !selectedEntity.value) return

  loadingHistory.value = true
  error.value = null
  result.value = null

  try {
    result.value = await fetchEntityHistory(slugifyEntityKey(selectedEntity.value.name), {
      [selectedAttribute.value]: identifierValue.value.trim(),
    })
  } catch (e) {
    const message = (e as { message?: string })?.message ?? 'Failed to fetch history'
    error.value = message
  } finally {
    loadingHistory.value = false
  }
}
</script>

<template>
  <aside class="flex w-96 flex-col gap-4 border-l border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
    <header class="flex items-center gap-2">
      <Search class="h-5 w-5 text-primary-600 dark:text-primary-500" aria-hidden="true" />
      <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Entity history</h2>
    </header>
    <p class="text-sm text-gray-500 dark:text-gray-400">
      Look up a single entity by its identifier and see input/output for every topology run that touched it.
    </p>

    <form class="space-y-3" @submit.prevent="handleSubmit">
      <div>
        <label for="ehp-entity" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Entity
        </label>
        <select
          id="ehp-entity"
          v-model="selectedEntityId"
          :disabled="loadingEntities || loadingHistory"
          class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
        >
          <option value="" disabled>{{ loadingEntities ? 'Loading…' : 'Select entity' }}</option>
          <option v-for="entity in entities" :key="entity.id" :value="entity.id">
            {{ entity.name }}
          </option>
        </select>
      </div>

      <div v-if="attributes.length > 1">
        <label for="ehp-attr" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          Identifier attribute
        </label>
        <select
          id="ehp-attr"
          v-model="selectedAttribute"
          :disabled="loadingHistory"
          class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
        >
          <option v-for="attr in attributes" :key="attr.name" :value="attr.name">
            {{ attr.description || attr.name }}
          </option>
        </select>
      </div>

      <div>
        <label for="ehp-value" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
          {{ attributes.length === 1 && attributes[0] ? attributes[0].description || attributes[0].name : 'Value' }}
        </label>
        <input
          id="ehp-value"
          v-model="identifierValue"
          type="text"
          :disabled="loadingHistory"
          placeholder="e.g. ord-017"
          class="mt-1 block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
        />
      </div>

      <Button
        type="submit"
        variant="primary"
        :disabled="!canSubmit || loadingHistory"
        class="w-full"
      >
        {{ loadingHistory ? 'Loading…' : 'Show history' }}
      </Button>
    </form>

    <div v-if="error" class="rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400">
      {{ error }}
    </div>

    <div v-if="result" class="flex-1 overflow-y-auto">
      <h3 class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
        Runs ({{ result.runs.length }})
      </h3>
      <div v-if="result.runs.length === 0" class="rounded-lg bg-gray-50 p-4 text-sm text-gray-500 dark:bg-gray-700 dark:text-gray-400">
        No topology runs touched this entity.
      </div>
      <div v-else class="space-y-3">
        <EntityRunCard v-for="run in result.runs" :key="run.correlationId" :run="run" />
      </div>
    </div>
  </aside>
</template>
