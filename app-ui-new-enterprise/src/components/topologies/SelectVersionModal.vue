<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import { fetchTopologyVersions } from '@/services/topologiesService'
import StatusBadge from '@/components/ui/StatusBadge.vue'
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
    versions.value = await fetchTopologyVersions(props.topologyName)
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

const getVersionBadge = (visibility: string, enabled: boolean) => ({
  variant: visibility === 'draft' ? 'gray' as const : enabled ? 'green' as const : 'red' as const,
  label: visibility === 'draft' ? 'Draft' : enabled ? 'Enabled' : 'Disabled',
})

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
            version.visibility === 'public' && version.enabled
              ? 'border border-green-600 dark:border-green-500 bg-green-50 dark:bg-green-900/20'
              : 'border border-gray-200 dark:border-gray-700'
        ]"
      >
        <div class="flex items-center justify-between">
          <span class="text-sm font-semibold text-gray-900 dark:text-white">
            Version {{ version.version }}
          </span>
          <StatusBadge :variant="getVersionBadge(version.visibility, version.enabled).variant">
            {{ getVersionBadge(version.visibility, version.enabled).label }}
          </StatusBadge>
        </div>
      </button>
    </div>

    <template #footer-actions>
      <Button variant="outline" @click="handleClose">
        Cancel
      </Button>
    </template>
  </Modal>
</template>

