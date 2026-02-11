<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import Drawer from '@/components/ui/Drawer.vue'
import Button from '@/components/ui/Button.vue'
import { fetchTopologyVersions } from '@/services/topologiesService'
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
          <span
            :class="[
              'text-xs font-medium px-2.5 py-0.5 rounded',
              getStatusBadgeClass(version.visibility, version.enabled)
            ]"
          >
            {{ getStatusLabel(version.visibility, version.enabled) }}
          </span>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          {{ version.updated }}
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

