<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import { fetchTopologyVersions, deleteTopology } from '@/services/topologiesService'
import type { TopologyVersion } from '@/types/topologies-page'

interface Props {
  modelValue: boolean
  topologyId: string
  topologyName: string
  currentVersionId?: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'deleted': [data: { topologyId: string; versionIds: string[]; isFullDelete: boolean }]
}>()

const versions = ref<TopologyVersion[]>([])
const loading = ref(false)
const deleting = ref(false)
const selectedVersionIds = ref<Set<string>>(new Set())

const modalTitle = computed(() =>
  props.topologyName ? `Delete - ${props.topologyName}` : 'Delete Topology',
)

const isAllSelected = computed(() =>
  versions.value.length > 0 && selectedVersionIds.value.size === versions.value.length,
)

const isCurrentVersionSelected = computed(() =>
  !!props.currentVersionId && selectedVersionIds.value.has(props.currentVersionId),
)

const hasSelection = computed(() => selectedVersionIds.value.size > 0)

const loadVersions = async () => {
  if (!props.topologyId) return
  loading.value = true
  try {
    versions.value = await fetchTopologyVersions(props.topologyName)
  } catch (error) {
    console.error('Failed to load versions:', error)
    versions.value = []
  } finally {
    loading.value = false
  }
}

watch(() => props.modelValue, (isOpen) => {
  if (isOpen && props.topologyId) {
    selectedVersionIds.value = new Set()
    loadVersions()
  }
})

const toggleVersion = (id: string) => {
  const next = new Set(selectedVersionIds.value)
  if (next.has(id)) {
    next.delete(id)
  } else {
    next.add(id)
  }
  selectedVersionIds.value = next
}

const selectAll = () => {
  selectedVersionIds.value = new Set(versions.value.map(v => v.id))
}

const selectCurrentVersion = () => {
  if (props.currentVersionId) {
    selectedVersionIds.value = new Set([props.currentVersionId])
  }
}

const handleClose = () => {
  emit('update:modelValue', false)
}

const handleDelete = async () => {
  if (!hasSelection.value) return
  deleting.value = true
  try {
    const ids = Array.from(selectedVersionIds.value)
    for (const id of ids) {
      await deleteTopology(id)
    }
    emit('deleted', {
      topologyId: props.topologyId,
      versionIds: ids,
      isFullDelete: isAllSelected.value,
    })
    emit('update:modelValue', false)
  } catch (error) {
    console.error('Failed to delete topology version(s):', error)
  } finally {
    deleting.value = false
  }
}

const getStatusBadgeClass = (visibility: string, enabled: boolean) => {
  if (visibility === 'draft') {
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
  }
  return enabled
    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
    : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
}

const getStatusLabel = (visibility: string, enabled: boolean) => {
  if (visibility === 'draft') return 'Draft'
  return enabled ? 'Enabled' : 'Disabled'
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="delete-topology-modal"
    :title="modalTitle"
    size="md"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
    </div>

    <div v-else class="space-y-3">
      <!-- Quick actions -->
      <div class="flex gap-2 mb-4">
        <button
          type="button"
          @click="selectAll"
          :class="[
            'flex-1 text-sm font-medium px-4 py-2 rounded-full border transition-colors',
            isAllSelected
              ? 'border-red-500 bg-red-50 text-red-700 dark:border-red-500 dark:bg-red-900/20 dark:text-red-400'
              : 'border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
          ]"
        >
          Delete entire topology
        </button>
        <button
          v-if="currentVersionId"
          type="button"
          @click="selectCurrentVersion"
          :class="[
            'flex-1 text-sm font-medium px-4 py-2 rounded-full border transition-colors',
            isCurrentVersionSelected && selectedVersionIds.size === 1
              ? 'border-red-500 bg-red-50 text-red-700 dark:border-red-500 dark:bg-red-900/20 dark:text-red-400'
              : 'border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
          ]"
        >
          Current version
        </button>
      </div>

      <!-- Version list -->
      <div
        v-for="version in versions"
        :key="version.id"
        @click="toggleVersion(version.id)"
        :class="[
          'flex items-center gap-3 rounded-lg p-3 cursor-pointer border transition-colors',
          selectedVersionIds.has(version.id)
            ? 'border-red-500 bg-red-50 dark:border-red-500 dark:bg-red-900/20'
            : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700'
        ]"
      >
        <input
          type="checkbox"
          :checked="selectedVersionIds.has(version.id)"
          class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700"
          @click.stop
          @change="toggleVersion(version.id)"
        />
        <div class="flex-1 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="text-sm font-semibold text-gray-900 dark:text-white">
              Version {{ version.version }}
            </span>
            <span
              v-if="version.id === currentVersionId"
              class="text-xs text-gray-500 dark:text-gray-400"
            >(current)</span>
          </div>
          <span
            :class="[
              'text-xs font-medium px-2.5 py-0.5 rounded',
              getStatusBadgeClass(version.visibility, version.enabled)
            ]"
          >
            {{ getStatusLabel(version.visibility, version.enabled) }}
          </span>
        </div>
      </div>

      <!-- Warning -->
      <p v-if="isAllSelected" class="text-xs text-red-600 dark:text-red-400 mt-2">
        This will permanently delete the topology and all its versions.
      </p>
    </div>

    <template #footer-actions>
      <Button variant="outline" @click="handleClose">
        Cancel
      </Button>
      <Button
        variant="danger"
        :loading="deleting"
        :disabled="!hasSelection"
        @click="handleDelete"
      >
        {{ isAllSelected ? 'Delete all versions' : `Delete ${selectedVersionIds.size} version(s)` }}
      </Button>
    </template>
  </Modal>
</template>
