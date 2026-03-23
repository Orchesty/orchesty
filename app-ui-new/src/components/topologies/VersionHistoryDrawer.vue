<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import Drawer from '@/components/ui/Drawer.vue'
import Button from '@/components/ui/Button.vue'
import { fetchTopologyVersions } from '@/services/topologiesService'
import { useDateFormat } from '@/composables/useDateFormat'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import type { TopologyVersion } from '@/types/topologies-page'

interface Props {
  modelValue: boolean
  topologyId: string
  topologyName?: string
  currentVersionId: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const router = useRouter()
const route = useRoute()
const { formatDateTime } = useDateFormat()
const versions = ref<TopologyVersion[]>([])
const loading = ref(false)

const loadVersions = async () => {
  if (!props.topologyName) return

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

// Load versions when drawer opens
watch(() => props.modelValue, (isOpen) => {
  if (isOpen && props.topologyName) {
    loadVersions()
  }
})

const getVersionBadge = (visibility: string, enabled: boolean) => ({
  variant: visibility === 'draft' ? 'gray' as const : enabled ? 'green' as const : 'red' as const,
  label: visibility === 'draft' ? 'Draft' : enabled ? 'Enabled' : 'Disabled',
})

const getVersionBorderClass = (versionId: string) => {
  return versionId === props.currentVersionId
    ? 'border-green-600 dark:border-green-500 bg-green-50 dark:bg-green-900/20'
    : 'border-gray-200 dark:border-gray-700'
}

const handleSwitchVersion = (versionId: string) => {
  emit('update:modelValue', false)
  // Navigate to same topology with different version
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
  <Drawer
    :model-value="modelValue"
    id="version-history-drawer"
    label="VERSION HISTORY"
    width="w-96"
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
        @click="handleSwitchVersion(version.id)"
        :class="[
          'w-full text-left rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors border',
          getVersionBorderClass(version.id)
        ]"
      >
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-semibold text-gray-900 dark:text-white">
            {{ version.version }}
          </span>
          <StatusBadge :variant="getVersionBadge(version.visibility, version.enabled).variant">
            {{ getVersionBadge(version.visibility, version.enabled).label }}
          </StatusBadge>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          {{ formatDateTime(version.updated) }}
        </p>
      </button>
    </div>

    <template #footer-actions>
      <Button variant="outline" @click="handleClose">
        Close
      </Button>
    </template>
  </Drawer>
</template>

