<script setup lang="ts">
import { ref, nextTick, onMounted, computed, watch, inject } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import TopologyProcessesTab from '@/components/topologies/TopologyProcessesTab.vue'
import TopologyLogsTab from '@/components/topologies/TopologyLogsTab.vue'
import TopologyFailedMessagesTab from '@/components/topologies/TopologyFailedMessagesTab.vue'
import HorizontalBarChart from '@/components/ui/HorizontalBarChart.vue'
import VersionHistoryDrawer from '@/components/topologies/VersionHistoryDrawer.vue'
import TopologyDesignerDrawer from '@/components/topologies/TopologyDesignerDrawer.vue'
import TopologyEditor from '@/components/topologies/TopologyEditor.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import type { BadgeVariant } from '@/components/ui/StatusBadge.vue'
import DeleteTopologyModal from '@/components/topologies/DeleteTopologyModal.vue'
import Modal from '@/components/ui/Modal.vue'
import Confirm from '@/components/ui/Confirm.vue'
import Button from '@/components/ui/Button.vue'
import MoreActions from '@/components/ui/MoreActions.vue'
import type { MoreActionsSection } from '@/components/ui/MoreActions.vue'
import Card from '@/components/ui/Card.vue'
import TabCard from '@/components/ui/TabCard.vue'
import Textarea from '@/components/ui/datagrid/Textarea.vue'
import { fetchTopologyDetail, fetchTopologySchema, fetchTopologyVersions, publishTopology, toggleTopologyEnabled, updateTopology, fetchCategoryBreadcrumb } from '@/services/topologiesService'
import { validateMcpManifest } from '@/utils/mcpManifestValidator'
import { fetchTopologyMetrics } from '@/services/topologyMetricsService'
import { useToast } from '@/composables/useToast'
import type { TopologyDetail, TopologyVersion, TopologyLayoutContext } from '@/types/topologies-page'
import type { TopologyMetrics, MetricsMode } from '@/types/topology-metrics'
import { useLastTopology } from '@/composables/useLastTopology'
import FailedMessageModal from '@/components/topologies/FailedMessageModal.vue'
import type { TrashItem } from '@/types/trash'

export interface TopologyTab {
  id: string
  label: string
}

interface Props {
  id: string
  extraTabs?: TopologyTab[]
  hiddenTabs?: string[]
}

const props = withDefaults(defineProps<Props>(), {
  extraTabs: () => [],
  hiddenTabs: () => [],
})
const route = useRoute()
const router = useRouter()

const { showToast } = useToast()
const { setLastTopology, getLastTopology } = useLastTopology()

// Inject shared layout context
const layout = inject<TopologyLayoutContext>('topologyLayout')!

// Register callbacks for layout events
layout.onTopologyEdited(async () => {
  if (topology.value) {
    topology.value = await fetchTopologyDetail(props.id, versionId.value)
    checkDescriptionTruncation()
  }
})

layout.onTopologyMoved(async () => {
  if (topology.value) {
    topology.value = await fetchTopologyDetail(props.id, versionId.value)
  }
})

const topologyEditorRef = ref<InstanceType<typeof TopologyEditor> | null>(null)

const topology = ref<TopologyDetail | null>(null)
const topologyVersions = ref<TopologyVersion[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const categoryPath = ref<string[]>([])
const versionDrawerOpen = ref(false)
const designerDrawerOpen = ref(false)

const showDisabledWarning = computed(() => {
  if (!topology.value) return false
  const hasPublished = topologyVersions.value.some(v => v.visibility === 'public')
  const anyEnabled = topologyVersions.value.some(v => v.enabled)
  return hasPublished && !anyEnabled
})

// Description popup
const descriptionPopupOpen = ref(false)
const isDescriptionTruncated = ref(false)

const checkDescriptionTruncation = () => {
  nextTick(() => {
    const el = document.querySelector('[data-description-text]') as HTMLElement | null
    if (el) {
      isDescriptionTruncated.value = el.scrollWidth > el.clientWidth
    } else {
      isDescriptionTruncated.value = false
    }
  })
}

const versionId = computed(() => route.query.version as string | undefined)

const statusBadgeVariant = computed<BadgeVariant>(() => {
  if (!topology.value) return 'gray'
  if (topology.value.visibility === 'draft') return 'gray'
  return topology.value.enabled ? 'green' : 'red'
})

const statusLabel = computed(() => {
  if (!topology.value) return ''
  if (topology.value.visibility === 'draft') return 'Draft'
  return topology.value.enabled ? 'Enabled' : 'Disabled'
})

// Metrics data
const metricsData = ref<TopologyMetrics | null>(null)
const metricsLoading = ref(false)
const metricsMode = ref<MetricsMode>('last-run')

// Active tab state
const lastTopology = getLastTopology()
const activeTopologyTab = ref<string>(
  (lastTopology && lastTopology.id === props.id && lastTopology.activeTab)
    ? lastTopology.activeTab
    : 'topology'
)

// Context tab state
const contextPlaceholder = `{
  "kind": "query",
  "input_schema": {
    "type": "object",
    "properties": {
      "location": {
        "type": "string"
      },
      "date": {
        "type": "string",
        "format": "date"
      }
    },
    "required": [
      "location"
    ]
  },
  "output_schema": {
    "type": "object",
    "properties": {
      "temperature": {
        "type": "number"
      },
      "humidity": {
        "type": "number"
      },
      "text": {
        "type": "string"
      }
    },
    "required": [
      "temperature",
      "humidity",
      "text"
    ]
  }
}`
const contextManifest = ref('')
const manifestError = ref('')

const isManifestValid = computed(() => {
  return contextManifest.value.trim() !== '' && manifestError.value === ''
})

// Tabs configuration
const coreTopologyTabs: TopologyTab[] = [
  { id: 'topology', label: 'Topology' },
  { id: 'context', label: 'Context' },
  { id: 'processes', label: 'Processes' },
  { id: 'logs', label: 'Logs' },
  { id: 'trash', label: 'Failed Messages' },
  { id: 'metrics', label: 'Metrics' },
]

const topologyTabs = computed(() =>
  [...coreTopologyTabs, ...props.extraTabs].filter(tab => !props.hiddenTabs.includes(tab.id))
)

const activeTabClass = 'text-primary-600 border-primary-600 dark:text-primary-500 dark:border-primary-500'
const inactiveTabClass = 'text-gray-500 border-transparent hover:text-gray-600 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'

// Refresh trigger -- incremented after process run to invalidate tab data
const refreshKey = ref(0)

// Shared failed message modal state (for the Failed Messages tab)
const failedTabModalOpen = ref(false)
const failedTabNodeId = ref('')
const failedTabCorrelationId = ref('')
const failedTabTopologyId = ref('')

const handleOpenFailedMessage = (item: TrashItem) => {
  failedTabNodeId.value = item.nodeId
  failedTabCorrelationId.value = item.correlationId
  failedTabTopologyId.value = item.topologyId
  failedTabModalOpen.value = true
}

const handleTabModalUpdate = () => {
  refreshKey.value++
}

const handleProcessRun = () => {
  refreshKey.value++
}

const handleEditTopology = () => {
  if (!topology.value) return
  layout.openEditTopologyModal(topology.value._id, topology.value.name, topology.value.description)
}

const handleMoveTopology = () => {
  if (!topology.value) return
  layout.openMoveTopologyModal(topology.value._id, topology.value.name, topology.value.category)
}

const enableDisableConfirmOpen = ref(false)
const pendingToggleAction = ref<'enable' | 'disable' | null>(null)
const enabledSiblingVersion = ref<{ id: string; version: string } | null>(null)

const deleteTopologyModalOpen = ref(false)

const handleDeleteTopologyAction = () => {
  if (!topology.value) return
  deleteTopologyModalOpen.value = true
}

const handleTopologyDeleted = async (data: { topologyId: string; versionIds: string[]; isFullDelete: boolean }) => {
  const label = data.isFullDelete ? 'Topology deleted successfully' : 'Version(s) deleted successfully'
  showToast(label, 'success')

  const currentVersionId = topology.value?._id
  const currentVersionDeleted = currentVersionId && data.versionIds.includes(currentVersionId)

  if (data.isFullDelete || currentVersionDeleted) {
    const { clearLastTopology } = useLastTopology()
    clearLastTopology()
    await layout.refreshSidebar()
    router.push({ name: 'topologies' })
  } else if (currentVersionId) {
    topology.value = await fetchTopologyDetail(currentVersionId)
    await layout.refreshSidebar()

    if (props.id !== currentVersionId || versionId.value) {
      router.replace({
        name: 'topology-detail',
        params: { id: currentVersionId },
      })
    }
  }
}

const handleCloneTopology = () => {
  if (!topology.value) return
  layout.handleCloneTopologyAction(topology.value._id)
}

const handleExportTopology = () => {
  if (!topology.value) return
  layout.handleExportTopologyAction(topology.value._id, topology.value.name)
}

const moreActionsSections: MoreActionsSection[] = [
  {
    items: [
      { type: 'button', label: 'Edit', onClick: handleEditTopology },
      { type: 'button', label: 'Move', onClick: handleMoveTopology },
      { type: 'button', label: 'Clone', onClick: handleCloneTopology },
      { type: 'button', label: 'Export', onClick: handleExportTopology },
    ],
  },
  {
    items: [
      {
        type: 'button',
        label: 'Delete',
        class: 'text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600 dark:hover:text-red-400',
        onClick: handleDeleteTopologyAction,
      },
    ],
  },
]

async function fetchWithRetry<T>(fn: () => Promise<T>, retries = 2, delayMs = 500): Promise<T> {
  for (let i = 0; i <= retries; i++) {
    try { return await fn() }
    catch (e) {
      if (i === retries) throw e
      await new Promise(r => setTimeout(r, delayMs * (i + 1)))
    }
  }
  throw new Error('unreachable')
}

const loadTopologyDetail = async () => {
  loading.value = true
  error.value = null
  try {
    topology.value = await fetchWithRetry(() => fetchTopologyDetail(props.id, versionId.value))

    if (topology.value) {
      fetchTopologyVersions(topology.value.name)
        .then(v => { topologyVersions.value = v })
        .catch(() => { topologyVersions.value = [] })
    }

    const lastTopology = getLastTopology()
    if (lastTopology && lastTopology.id === props.id && lastTopology.activeTab) {
      activeTopologyTab.value = lastTopology.activeTab
      if (lastTopology.activeTab === 'metrics') {
        await loadMetrics()
      }
    } else {
      activeTopologyTab.value = 'topology'
    }

    if (topology.value) {
      setLastTopology({
        id: props.id,
        name: topology.value.name,
        versionId: versionId.value,
        activeTab: activeTopologyTab.value
      })
    }
    const mcpDesc = topology.value?.mcp_description
    contextManifest.value =
      mcpDesc && !Array.isArray(mcpDesc) && Object.keys(mcpDesc).length > 0
        ? JSON.stringify(mcpDesc, null, 2)
        : ''

    checkDescriptionTruncation()

    if (topology.value) {
      try {
        const schema = await fetchTopologySchema(topology.value._id)
        const nodes = schema?.nodes ?? []
        hasFlow.value = Array.isArray(nodes) && nodes.some((n: { type?: string }) => n.type === 'flow')
      } catch {
        hasFlow.value = false
      }

      if (topology.value.category) {
        try {
          categoryPath.value = await fetchCategoryBreadcrumb(topology.value.category)
        } catch {
          categoryPath.value = []
        }
      } else {
        categoryPath.value = []
      }
    }
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load topology'
    console.error('Failed to load topology:', err)
  } finally {
    loading.value = false
  }
}

const publishing = ref(false)
const hasFlow = ref(false)

const handlePublish = async () => {
  if (!topology.value) return
  publishing.value = true
  try {
    await publishTopology(topology.value._id)
    showToast('Topology published successfully', 'success')
    topology.value = await fetchTopologyDetail(props.id, versionId.value)
    await layout.refreshSidebar()
  } catch (error) {
    console.error('Failed to publish topology:', error)
    showToast('Failed to publish topology', 'error')
  } finally {
    publishing.value = false
  }
}

const handleToggleEnabled = async () => {
  if (!topology.value) return
  if (topology.value.enabled) {
    pendingToggleAction.value = 'disable'
    enabledSiblingVersion.value = null
    enableDisableConfirmOpen.value = true
  } else {
    try {
      const versions = await fetchTopologyVersions(topology.value.name)
      const sibling = versions.find(v => v.enabled && v.id !== topology.value!._id)
      if (sibling) {
        enabledSiblingVersion.value = sibling
        pendingToggleAction.value = 'enable'
        enableDisableConfirmOpen.value = true
      } else {
        executeToggleEnabled(true)
      }
    } catch {
      executeToggleEnabled(true)
    }
  }
}

const executeToggleEnabled = async (newEnabled: boolean) => {
  if (!topology.value) return
  try {
    await toggleTopologyEnabled(topology.value._id, newEnabled)
    showToast(`Topology ${newEnabled ? 'enabled' : 'disabled'} successfully`, 'success')
    topology.value = await fetchTopologyDetail(props.id, versionId.value)
    if (topology.value) {
      topologyVersions.value = await fetchTopologyVersions(topology.value.name)
    }
    await layout.refreshSidebar()
  } catch (error) {
    console.error('Failed to toggle topology:', error)
    showToast('Failed to toggle topology state', 'error')
  }
}

const handleConfirmToggle = () => {
  enableDisableConfirmOpen.value = false
  if (pendingToggleAction.value === 'enable') {
    executeToggleEnabled(true)
  } else if (pendingToggleAction.value === 'disable') {
    executeToggleEnabled(false)
  }
  pendingToggleAction.value = null
}

const handleVersionsClick = () => {
  versionDrawerOpen.value = true
}

const handleOpenDesigner = () => {
  designerDrawerOpen.value = true
}

const handleSaveDesign = async (data: { _id: string }) => {
  if (topology.value && data._id !== topology.value._id) {
    await router.replace({ query: { version: data._id } })
    await layout.refreshSidebar()
  } else {
    topologyEditorRef.value?.reloadSchema()
  }

  const targetId = data._id || topology.value?._id
  if (targetId) {
    try {
      const schema = await fetchTopologySchema(targetId)
      const nodes = schema?.nodes ?? []
      hasFlow.value = Array.isArray(nodes) && nodes.some((n: { type?: string }) => n.type === 'flow')
    } catch {
      hasFlow.value = false
    }
  }
}

// Context tab handlers
const savingContext = ref(false)

watch(contextManifest, (text) => {
  const trimmed = text.trim()
  if (!trimmed) {
    manifestError.value = ''
    return
  }
  let parsed
  try {
    parsed = JSON.parse(trimmed)
  } catch {
    manifestError.value = 'Invalid JSON syntax'
    return
  }
  const result = validateMcpManifest(parsed)
  manifestError.value = result.valid ? '' : (result.error || 'Validation failed')
})

const handleSaveContext = async () => {
  if (!topology.value) return

  let parsed: Record<string, unknown>
  try {
    parsed = JSON.parse(contextManifest.value)
  } catch {
    manifestError.value = 'Invalid JSON syntax'
    return
  }

  const validation = validateMcpManifest(parsed)
  if (!validation.valid) {
    manifestError.value = validation.error || 'Validation failed'
    return
  }

  savingContext.value = true
  try {
    await updateTopology(topology.value._id, { mcp_description: parsed })
    showToast('MCP Manifest saved successfully', 'success')
  } catch (error) {
    console.error('Failed to save MCP Manifest:', error)
    showToast('Failed to save MCP Manifest', 'error')
  } finally {
    savingContext.value = false
  }
}

// Load metrics data
const loadMetrics = async () => {
  const topologyId = topology.value?._id || props.id
  if (!topologyId) return
  metricsLoading.value = true
  try {
    metricsData.value = await fetchTopologyMetrics(topologyId, metricsMode.value)
  } catch (err) {
    console.error('Failed to load metrics:', err)
  } finally {
    metricsLoading.value = false
  }
}

const handleMetricsModeChange = (mode: MetricsMode) => {
  metricsMode.value = mode
  metricsData.value = null
  loadMetrics()
}

const handleTabChange = (tabId: string) => {
  activeTopologyTab.value = tabId
  if (tabId === 'metrics' && !metricsData.value) {
    loadMetrics()
  }
  if (topology.value) {
    setLastTopology({
      id: props.id,
      name: topology.value.name,
      versionId: versionId.value,
      activeTab: tabId
    })
  }
}

watch(
  () => [props.id, versionId.value],
  async () => {
    metricsData.value = null
    await loadTopologyDetail()
  }
)

onMounted(async () => {
  await loadTopologyDetail()
})
</script>

<template>
  <!-- Loading State -->
  <div v-if="loading" class="flex items-center justify-center h-full">
    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
  </div>

  <!-- Error State -->
  <div v-else-if="error" class="flex items-center justify-center h-full">
    <div class="text-center">
      <p class="text-red-600 dark:text-red-400">{{ error }}</p>
    </div>
  </div>

  <!-- Topology Detail -->
  <div v-else-if="topology" class="flex flex-col h-full px-4 pt-2 overflow-hidden">
    <!-- Page Header -->
    <div class="mb-4 shrink-0">
      <div class="flex items-center justify-between mb-2">
        <!-- Toggle Sidebar Button + Breadcrumb -->
        <div>
          <div class="flex items-center gap-2 mb-1">
            <button
              type="button"
              @click="layout.topologySidebarCollapsed.value = !layout.topologySidebarCollapsed.value"
              class="inline-flex items-center justify-center rounded-lg p-0 relative -left-1 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            >
              <svg
                v-show="layout.topologySidebarCollapsed.value"
                class="w-6 h-6"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                height="24px"
                viewBox="0 -960 960 960"
                width="24px"
                fill="currentColor"
              >
                <path d="M498.08-623.46v286.92L641.92-480 498.08-623.46ZM212.31-140q-29.92 0-51.12-21.19Q140-182.39 140-212.31v-535.38q0-29.92 21.19-51.12Q182.39-820 212.31-820h535.38q29.92 0 51.12 21.19Q820-777.61 820-747.69v535.38q0 29.92-21.19 51.12Q777.61-140 747.69-140H212.31ZM320-200v-560H212.31q-4.62 0-8.46 3.85-3.85 3.84-3.85 8.46v535.38q0 4.62 3.85 8.46 3.84 3.85 8.46 3.85H320Zm60 0h367.69q4.62 0 8.46-3.85 3.85-3.84 3.85-8.46v-535.38q0-4.62-3.85-8.46-3.84-3.85-8.46-3.85H380v560Zm-60 0H200h120Z" />
              </svg>
              <svg
                v-show="!layout.topologySidebarCollapsed.value"
                class="w-6 h-6"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                height="24px"
                viewBox="0 -960 960 960"
                width="24px"
                fill="currentColor"
              >
                <path d="M641.92-336.54v-286.92L498.08-480l143.84 143.46ZM212.31-140q-29.92 0-51.12-21.19Q140-182.39 140-212.31v-535.38q0-29.92 21.19-51.12Q182.39-820 212.31-820h535.38q29.92 0 51.12 21.19Q820-777.61 820-747.69v535.38q0 29.92-21.19 51.12Q777.61-140 747.69-140H212.31ZM320-200v-560H212.31q-4.62 0-8.46 3.85-3.85 3.84-3.85 8.46v535.38q0 4.62 3.85 8.46 3.84 3.85 8.46 3.85H320Zm60 0h367.69q4.62 0 8.46-3.85 3.85-3.84 3.85-8.46v-535.38q0-4.62-3.85-8.46-3.84-3.85-8.46-3.85H380v560Zm-60 0H200h120Z" />
              </svg>
              <span class="sr-only">Toggle sidebar</span>
            </button>

            <!-- Breadcrumb -->
            <nav class="flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
              <span>Topologies</span>
              <template v-for="folder in categoryPath" :key="folder">
                <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span>{{ folder }}</span>
              </template>
            </nav>
          </div>

          <!-- Title row -->
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ topology.name }}</h1>
            <span class="text-sm text-gray-400 dark:text-gray-500 font-normal">v.{{ topology.version }}</span>
            <StatusBadge :variant="statusBadgeVariant">{{ statusLabel }}</StatusBadge>
          </div>

          <div v-if="topology.description" class="flex items-center gap-1 mt-1 max-w-xl">
            <p data-description-text class="text-sm text-gray-500 dark:text-gray-400 overflow-hidden whitespace-nowrap">{{ topology.description }}</p>
            <button
              v-if="isDescriptionTruncated"
              type="button"
              @click="descriptionPopupOpen = true"
              class="shrink-0 text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
            >...</button>
          </div>
        </div>

        <!-- Topology disabled warning -->
        <div v-if="showDisabledWarning" class="flex items-center gap-3 rounded-lg border border-yellow-300 bg-yellow-50 px-4 py-3 text-yellow-800 dark:border-yellow-600 dark:bg-yellow-900/30 dark:text-yellow-300">
          <svg class="h-6 w-6 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM10 15a1 1 0 1 1 0-2 1 1 0 0 1 0 2Zm1-4a1 1 0 0 1-2 0V6a1 1 0 0 1 2 0v5Z"/>
          </svg>
          <span class="text-sm font-semibold">Topology disabled!</span>
        </div>

        <div class="flex items-center gap-2">
          <Button v-if="topology.visibility === 'draft'" :loading="publishing" :disabled="!hasFlow" @click="handlePublish">
            <template #prepend>
              <svg class="-ms-1 me-2 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path fill-rule="evenodd" d="M5 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h11.5c.07 0 .14-.007.207-.021.095.014.193.021.293.021h2a2 2 0 0 0 2-2V7a1 1 0 0 0-1-1h-1a1 1 0 1 0 0 2v11h-2V5a2 2 0 0 0-2-2H5Zm7 4a1 1 0 0 1 1-1h.5a1 1 0 1 1 0 2H13a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h.5a1 1 0 1 1 0 2H13a1 1 0 0 1-1-1Zm-6 4a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H7a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H7a1 1 0 0 1-1-1ZM7 6a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H7Zm1 3V8h1v1H8Z" clip-rule="evenodd"/>
              </svg>
            </template>
            {{ publishing ? 'Publishing...' : 'Publish' }}
          </Button>
          <Button
            v-if="topology.visibility !== 'draft'"
            :variant="topology.enabled ? 'outline' : 'success'"
            @click="handleToggleEnabled"
          >
            {{ topology.enabled ? 'Disable' : 'Enable' }}
          </Button>
          <Button variant="outline" @click="handleOpenDesigner">
            <svg class="w-5 h-5 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
              <path d="M160-120v-170l527-526q12-12 27-18t30-6q16 0 30.5 6t25.5 18l56 56q12 11 18 25.5t6 30.5q0 15-6 30t-18 27L330-120H160Zm80-80h56l393-392-28-29-29-28-392 393v56Zm560-503-57-57 57 57Zm-139 82-29-28 57 57-28-29ZM560-120q74 0 137-37t63-103q0-36-19-62t-51-45l-59 59q23 10 36 22t13 26q0 23-36.5 41.5T560-200q-17 0-28.5 11.5T520-160q0 17 11.5 28.5T560-120ZM183-426l60-60q-20-8-31.5-16.5T200-520q0-12 18-24t76-37q88-38 117-69t29-70q0-55-44-87.5T280-840q-45 0-80.5 16T145-785q-11 13-9 29t15 26q13 11 29 9t27-13q14-14 31-20t42-6q41 0 60.5 12t19.5 28q0 14-17.5 25.5T262-654q-80 35-111 63.5T120-520q0 32 17 54.5t46 39.5Z"/>
            </svg>
            Design
          </Button>
          <Button variant="outline" @click="handleVersionsClick">
            <svg class="w-5 h-5 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
              <path d="M480-120q-138 0-240.5-91.5T122-440h82q14 104 92.5 172T480-200q117 0 198.5-81.5T760-480q0-117-81.5-198.5T480-760q-69 0-129 32t-101 88h110v80H120v-240h80v94q51-64 124.5-99T480-840q75 0 140.5 28.5t114 77q48.5 48.5 77 114T840-480q0 75-28.5 140.5t-77 114q-48.5 48.5-114 77T480-120Zm112-192L440-464v-216h80v184l128 128-56 56Z"/>
            </svg>
            Versions
          </Button>
          <MoreActions
            id="topology-more-actions"
            :sections="moreActionsSections"
          />
        </div>
      </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="shrink-0 border-b border-gray-200 dark:border-gray-700">
      <ul class="-mb-px flex flex-wrap text-center text-sm font-medium" role="tablist">
        <li v-for="tab in topologyTabs" :key="tab.id" class="mr-2" role="presentation">
          <button
            class="inline-block rounded-t-lg border-b-2 p-4"
            :class="activeTopologyTab === tab.id ? activeTabClass : inactiveTabClass"
            type="button"
            role="tab"
            :aria-selected="activeTopologyTab === tab.id"
            @click="handleTabChange(tab.id)"
          >
            {{ tab.label }}
          </button>
        </li>
      </ul>
    </div>

    <!-- Tabs Content -->

    <!-- Topology (editor) — fills remaining vertical space -->
    <div v-show="activeTopologyTab === 'topology'" class="flex-1 min-h-0 mt-4">
      <div class="h-full dark:bg-gray-800 dark:shadow-sm dark:rounded-lg">
        <div class="h-full bg-gray-50 dark:bg-gray-900 dark:rounded-lg overflow-hidden">
          <TopologyEditor ref="topologyEditorRef" :topology-id="topology._id" :topology-enabled="topology.enabled" :refresh-key="refreshKey" @process-run="handleProcessRun" />
        </div>
      </div>
    </div>

    <!-- Non-editor tabs — scrollable -->
    <div v-show="activeTopologyTab !== 'topology'" class="flex-1 min-h-0 overflow-y-auto pt-4 pb-4">

    <!-- Context -->
    <div v-show="activeTopologyTab === 'context'">
      <TabCard>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">MCP Manifest</h3>
        <form @submit.prevent="handleSaveContext" class="space-y-6">
          <div>
            <Textarea
              v-model="contextManifest"
              :placeholder="contextPlaceholder"
              :rows="16"
              :error="manifestError"
            />
          </div>
          <div class="pt-4">
            <Button type="submit" :disabled="!isManifestValid || savingContext">
              {{ savingContext ? 'Saving...' : 'Save' }}
            </Button>
          </div>
        </form>
      </TabCard>
    </div>

    <!-- Extension point for extra tab content -->
    <slot name="extra-tab-content" :active-tab="activeTopologyTab" :topology="topology" />

    <!-- Data tabs with KeepAlive for lazy loading + caching -->
    <KeepAlive>
      <TopologyProcessesTab
        v-if="activeTopologyTab === 'processes'"
        :topology-id="topology._id"
        :topology-name="topology.name"
        :refresh-key="refreshKey"
      />
      <TopologyLogsTab
        v-else-if="activeTopologyTab === 'logs'"
        :topology-id="topology._id"
        :topology-name="topology.name"
        :refresh-key="refreshKey"
      />
      <TopologyFailedMessagesTab
        v-else-if="activeTopologyTab === 'trash'"
        :topology-id="topology._id"
        :topology-name="topology.name"
        :refresh-key="refreshKey"
        @open-drawer="handleOpenFailedMessage"
      />
    </KeepAlive>

    <!-- Metrics -->
    <div v-show="activeTopologyTab === 'metrics'">
      <div v-if="metricsLoading" class="flex items-center justify-center py-12">
        <div role="status">
          <svg aria-hidden="true" class="w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-primary-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
            <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
          </svg>
          <span class="sr-only">Loading...</span>
        </div>
      </div>

      <div v-else-if="metricsData" class="space-y-4">
        <div class="flex items-center justify-end">
          <div class="inline-flex rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-0.5">
            <button
              type="button"
              class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors"
              :class="metricsMode === 'last-run'
                ? 'bg-primary-600 text-white shadow-xs'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
              @click="handleMetricsModeChange('last-run')"
            >
              Last Run
            </button>
            <button
              type="button"
              class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors"
              :class="metricsMode === 'average'
                ? 'bg-primary-600 text-white shadow-xs'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
              @click="handleMetricsModeChange('average')"
            >
              Average
            </button>
          </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <Card>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Node Process Time</h3>
            <div class="mb-4 relative overflow-visible">
              <HorizontalBarChart :data="metricsData.nodeProcessTimes.map(n => ({ label: n.nodeName, value: n.time }))" />
            </div>
          </Card>
          <Card>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Connector Request Time</h3>
            <div class="mb-4 relative overflow-visible">
              <HorizontalBarChart :data="metricsData.connectorRequestTimes.map(c => ({ label: c.connectorName, value: c.time }))" />
            </div>
          </Card>
        </div>
      </div>

      <div v-else class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
        <p class="text-sm text-gray-500 dark:text-gray-400">No metrics data available</p>
      </div>
    </div>

    </div><!-- /non-editor scrollable wrapper -->
  </div>

  <!-- Version History Drawer -->
  <VersionHistoryDrawer
    v-if="topology"
    v-model="versionDrawerOpen"
    :topology-id="topology._id"
    :topology-name="topology.name"
    :current-version-id="topology._id"
    placement="right"
  />

  <!-- Topology Designer Drawer -->
  <TopologyDesignerDrawer
    v-if="topology"
    v-model="designerDrawerOpen"
    :topology-id="topology._id"
    :topology-name="topology.name"
    :topology-version="topology.version"
    @save="handleSaveDesign"
  />

  <!-- Description Popup Modal -->
  <Modal
    v-model="descriptionPopupOpen"
    id="description-popup-modal"
    :title="topology?.name || 'Description'"
    size="md"
  >
    <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-wrap">{{ topology?.description }}</p>
  </Modal>

  <!-- Delete Topology Modal -->
  <DeleteTopologyModal
    v-if="topology"
    v-model="deleteTopologyModalOpen"
    :topology-id="topology._id"
    :topology-name="topology.name"
    :current-version-id="topology._id"
    @deleted="handleTopologyDeleted"
  />

  <!-- Enable/Disable Confirm -->
  <Confirm
    v-model="enableDisableConfirmOpen"
    id="enable-disable-confirm"
    :confirm-text="pendingToggleAction === 'enable' ? 'Yes, switch' : 'Yes, disable'"
    :confirm-variant="pendingToggleAction === 'enable' ? 'primary' : 'danger'"
    @confirm="handleConfirmToggle"
    @cancel="pendingToggleAction = null"
  >
    <template v-if="pendingToggleAction === 'enable'">
      <p class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">Switch active version?</p>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        Enabling version {{ topology?.version }} will disable currently active version {{ enabledSiblingVersion?.version }}.
      </p>
    </template>
    <template v-else>
      <p class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">Disable topology?</p>
      <p class="text-sm text-gray-500 dark:text-gray-400">
        No version of <strong>{{ topology?.name }}</strong> will be active after this action.
      </p>
    </template>
  </Confirm>

  <!-- Failed Message Modal (for Failed Messages tab) -->
  <FailedMessageModal
    v-model="failedTabModalOpen"
    :topology-id="failedTabTopologyId"
    :node-id="failedTabNodeId"
    :correlation-id="failedTabCorrelationId"
    node-name=""
    hide-bulk-actions
    @update="handleTabModalUpdate"
  />
</template>
