<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import { fetchTopologyVersions } from '@/services/topologiesService'
import type { TopologyVersion } from '@/types/topologies-page'
import { useLastTopology } from '@/composables/useLastTopology'

interface Props {
  modelValue: boolean
  topologyId: string
  topologyName: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const router = useRouter()
const { setLastTopology } = useLastTopology()
const versions = ref<TopologyVersion[]>([])
const loading = ref(false)

const modalTitle = computed(() => {
  return props.topologyName ? `Select Version - ${props.topologyName}` : 'Select Version'
})

const loadVersions = async () => {
  if (!props.topologyId) return
  
  loading.value = true
  try {
    versions.value = await fetchTopologyVersions(props.topologyId)
  } catch (error) {
    console.error('Failed to load versions:', error)
    versions.value = []
  } finally {
    loading.value = false
  }
}

// Load versions when modal opens
watch(() => props.modelValue, (isOpen) => {
  if (isOpen && props.topologyId) {
    loadVersions()
  }
})

const getStatusBadgeClass = (visibility: string, status: string) => {
  // Highlight running public versions
  if (visibility === 'public' && status === 'Running') {
    return 'bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-300'
  }
  // Draft versions
  if (visibility === 'draft') {
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
  }
  // Stopped versions
  if (status === 'Stopped') {
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
  }
  // Default
  return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
}

const getStatusLabel = (visibility: string, status: string) => {
  if (visibility === 'draft') {
    return 'Draft'
  }
  return status
}

const handleSelectVersion = (versionId: string) => {
  emit('update:modelValue', false)
  
  // Save selected version to localStorage
  setLastTopology({
    id: props.topologyId,
    name: props.topologyName,
    versionId
  })
  
  // Navigate to topology detail with version parameter
  router.push({
    name: 'topology-detail',
    params: { id: props.topologyId },
    query: { version: versionId }
  })
}

const handleClose = () => {
  emit('update:modelValue', false)
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="select-version-modal"
    :title="modalTitle"
    size="md"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
    </div>

    <!-- Version List -->
    <div v-else class="space-y-4">
      <button
        v-for="version in versions"
        :key="version.id"
        type="button"
        @click="handleSelectVersion(version.id)"
        :class="[
          'w-full text-left rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors',
          version.visibility === 'public' && version.status === 'Running'
            ? 'border border-primary-600 dark:border-primary-500 bg-primary-50 dark:bg-primary-900/20'
            : 'border border-gray-200 dark:border-gray-700'
        ]"
      >
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-semibold text-gray-900 dark:text-white">
            {{ version.version }}
          </span>
          <span
            :class="[
              'text-xs font-medium px-2.5 py-0.5 rounded',
              getStatusBadgeClass(version.visibility, version.status)
            ]"
          >
            {{ getStatusLabel(version.visibility, version.status) }}
          </span>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          {{ version.updated }}
        </p>
      </button>
    </div>

    <template #footer-actions>
      <Button variant="outline" @click="handleClose">
        Cancel
      </Button>
    </template>
  </Modal>
</template>

