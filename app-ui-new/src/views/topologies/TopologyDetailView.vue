<script setup lang="ts">
import { ref, nextTick, onMounted, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import AppSidebar from '@/components/layout/AppSidebar.vue'
import TopologiesSidebar from '@/components/topologies/TopologiesSidebar.vue'
import TopologyProcessesTab from '@/components/topologies/TopologyProcessesTab.vue'
import TopologyLogsTab from '@/components/topologies/TopologyLogsTab.vue'
import TopologyFailedMessagesTab from '@/components/topologies/TopologyFailedMessagesTab.vue'
import NodeProcessTimeChart from '@/components/topologies/NodeProcessTimeChart.vue'
import ConnectorRequestTimeChart from '@/components/topologies/ConnectorRequestTimeChart.vue'
import VersionHistoryDrawer from '@/components/topologies/VersionHistoryDrawer.vue'
import TopologyDesignerDrawer from '@/components/topologies/TopologyDesignerDrawer.vue'
import TopologyEditor from '@/components/topologies/TopologyEditor.vue'
import NewTopologyModal from '@/components/topologies/NewTopologyModal.vue'
import NewFolderModal from '@/components/topologies/NewFolderModal.vue'
import RenameFolderModal from '@/components/topologies/RenameFolderModal.vue'
import SelectVersionModal from '@/components/topologies/SelectVersionModal.vue'
import MoveTopologyModal from '@/components/topologies/MoveTopologyModal.vue'
import EditTopologyModal from '@/components/topologies/EditTopologyModal.vue'
import Confirm from '@/components/ui/Confirm.vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import MoreActions from '@/components/ui/MoreActions.vue'
import type { MoreActionsSection } from '@/components/ui/MoreActions.vue'
import TabsComponent, { type Tab } from '@/components/ui/Tabs.vue'
import Card from '@/components/ui/Card.vue'
import TabCard from '@/components/ui/TabCard.vue'
import Textarea from '@/components/ui/datagrid/Textarea.vue'
import { fetchTopologyDetail, fetchCategories, deleteCategory, publishTopology, toggleTopologyEnabled, deleteTopology, runTopology, cloneTopology, fetchTopologySchema, updateTopology } from '@/services/topologiesService'
import { validateMcpManifest } from '@/utils/mcpManifestValidator'
import { fetchTopologyMetrics } from '@/services/topologyMetricsService'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'
import { useToast } from '@/composables/useToast'
import type { TopologyDetail } from '@/types/topologies-page'
import type { TopologyMetrics } from '@/types/topology-metrics'
import type { FolderItem, TopologiesTreeNode } from '@/types/topologies-page'
import { Dropdown } from 'flowbite'
import { useLastTopology } from '@/composables/useLastTopology'
import TraceDrawer from '@/components/trace/TraceDrawer.vue'
import { useTraceDrawer } from '@/composables/useTraceDrawer'

interface Props {
  id: string
}

const props = defineProps<Props>()
const route = useRoute()
const router = useRouter()

const { showToast } = useToast()
const { setLastTopology, getLastTopology, clearLastTopology } = useLastTopology()
const { isTraceDrawerOpen } = useTraceDrawer()

// Sidebar ref for refreshTree
const sidebarRef = ref<InstanceType<typeof TopologiesSidebar> | null>(null)
const topologyEditorRef = ref<InstanceType<typeof TopologyEditor> | null>(null)

const topology = ref<TopologyDetail | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)
const versionDrawerOpen = ref(false)
const designerDrawerOpen = ref(false)

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

// Topology action modals
const editTopologyModalOpen = ref(false)
const moveTopologyModalOpen = ref(false)
const deleteTopologyConfirmOpen = ref(false)
const comingSoonConfirmOpen = ref(false)
const comingSoonFeature = ref('')

// Sidebar action target (when actions are triggered from sidebar on a different topology)
const sidebarActionTopologyId = ref('')
const sidebarActionTopologyName = ref('')

// Initialize sidebar collapsed state from localStorage to match sidebar's initial state
const getSidebarCollapsedFromStorage = (): boolean => {
  const saved = localStorage.getItem('topologySidebarCollapsed')
  return saved === 'true'
}
const topologySidebarCollapsed = ref(getSidebarCollapsedFromStorage())

// Metrics data
const metricsData = ref<TopologyMetrics | null>(null)
const metricsLoading = ref(false)

// Active tab state - initialize from localStorage or default to first tab
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

// Access tab state
interface AccessGroup {
  id: string
  name: string
  permission: 'manager' | 'developer' | 'user'
}

const accessGroups = ref<AccessGroup[]>([
  { id: 'group-1', name: 'Administrators', permission: 'manager' },
  { id: 'group-2', name: 'Developers', permission: 'developer' }
])

const availableGroups = computed(() => [
  'Administrators',
  'Developers',
  'Operators',
  'Support Team',
  'QA Team'
])

// Tabs configuration
const topologyTabs: Tab[] = [
  { id: 'topology', label: 'Topology', target: 'topology-content' },
  { id: 'context', label: 'Context', target: 'context-content' },
  { id: 'access', label: 'Access', target: 'access-content' },
  { id: 'processes', label: 'Processes', target: 'processes-content' },
  { id: 'logs', label: 'Logs', target: 'logs-content' },
  { id: 'trash', label: 'Failed Messages', target: 'trash-content' },
  { id: 'metrics', label: 'Metrics', target: 'metrics-content' }
]

// Sidebar modal state
const newTopologyModalOpen = ref(false)
const newFolderModalOpen = ref(false)
const renameFolderModalOpen = ref(false)
const deleteFolderConfirmOpen = ref(false)
const selectVersionModalOpen = ref(false)
const selectedTopologyId = ref('')
const selectedTopologyName = ref('')

// Active folder for CRUD operations
const activeFolderId = ref('')
const activeFolderName = ref('')
const activeFolderParentId = ref<string | null>(null)

const versionId = computed(() => route.query.version as string | undefined)

// Folders loaded from API (for dropdown menus)
const allFolders = ref<FolderItem[]>([])

// Compute set of folder IDs that have any content (topologies or subfolders)
const nonEmptyFolderIds = computed<Set<string>>(() => {
  const ids = new Set<string>()
  const walk = (nodes: TopologiesTreeNode[]) => {
    for (const node of nodes) {
      if (node.type === 'folder' && node.children.length > 0) {
        ids.add(node.id)
        walk(node.children)
      }
    }
  }
  walk(sidebarRef.value?.treeData ?? [])
  return ids
})

const statusBadgeClass = computed(() => {
  if (!topology.value) return ''
  if (topology.value.visibility === 'draft') {
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
  }
  return topology.value.enabled
    ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
    : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
})

const statusLabel = computed(() => {
  if (!topology.value) return ''
  if (topology.value.visibility === 'draft') return 'Draft'
  return topology.value.enabled ? 'Enabled' : 'Disabled'
})

// Topology action handlers (from detail page header)
const handleRunTopology = async () => {
  if (!topology.value) return
  try {
    await runTopology(topology.value._id)
    showToast('Topology run started', 'success')
  } catch (error) {
    console.error('Failed to run topology:', error)
    showToast('Failed to run topology', 'error')
  }
}

const handleEditTopology = () => {
  if (topology.value) {
    sidebarActionTopologyId.value = topology.value._id
    sidebarActionTopologyName.value = topology.value.name
  }
  editTopologyModalOpen.value = true
}

const handleMoveTopology = () => {
  if (topology.value) {
    sidebarActionTopologyId.value = topology.value._id
    sidebarActionTopologyName.value = topology.value.name
  }
  moveTopologyModalOpen.value = true
}

const handleDeleteTopologyAction = () => {
  if (topology.value) {
    sidebarActionTopologyId.value = topology.value._id
    sidebarActionTopologyName.value = topology.value.name
  }
  deleteTopologyConfirmOpen.value = true
}

const handleConfirmDeleteTopology = async () => {
  if (!sidebarActionTopologyId.value) return
  const deletedId = sidebarActionTopologyId.value
  const isCurrentTopology = topology.value && topology.value._id === deletedId
  try {
    await deleteTopology(deletedId)
    showToast('Topology deleted successfully', 'success')
    deleteTopologyConfirmOpen.value = false

    // If the deleted topology is the current one, clear lastTopology and navigate away
    if (isCurrentTopology) {
      clearLastTopology()
      router.push({ name: 'topologies' })
    } else {
      await refreshAfterCrud()
    }
  } catch (error) {
    console.error('Failed to delete topology:', error)
    showToast('Failed to delete topology', 'error')
  }
}

const handleCloneTopology = async (topologyId?: string) => {
  const id = topologyId || topology.value?._id
  if (!id) return
  try {
    const result = await cloneTopology(id)
    showToast('Topology cloned successfully', 'success')
    await sidebarRef.value?.refreshTree()
    router.push({ name: 'topology-detail', params: { id: result._id }, query: { version: result._id } })
  } catch (error) {
    console.error('Failed to clone topology:', error)
    showToast('Failed to clone topology', 'error')
  }
}

const handleExportTopology = async (topologyId?: string, topologyName?: string) => {
  const id = topologyId || topology.value?._id
  const name = topologyName || topology.value?.name
  if (!id || !name) return
  try {
    const schema = await fetchTopologySchema(id)
    const jsonString = JSON.stringify(schema, null, 4) + '\n'
    const blob = new Blob([jsonString], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `${name}.json`
    a.click()
    URL.revokeObjectURL(url)
    showToast('Topology exported successfully', 'success')
  } catch (error) {
    console.error('Failed to export topology:', error)
    showToast('Failed to export topology', 'error')
  }
}

const handleComingSoon = (feature: string) => {
  comingSoonFeature.value = feature
  comingSoonConfirmOpen.value = true
}

const handleTopologyEdited = async () => {
  // Re-fetch detail to update description
  if (topology.value) {
    topology.value = await fetchTopologyDetail(props.id, versionId.value)
    checkDescriptionTruncation()
  }
}

const handleTopologyMoved = async () => {
  // Re-fetch detail and refresh sidebar tree
  if (topology.value) {
    topology.value = await fetchTopologyDetail(props.id, versionId.value)
  }
  await refreshAfterCrud()
}

const moreActionsSections: MoreActionsSection[] = [
  {
    items: [
      {
        type: 'button',
        label: 'Run',
        onClick: handleRunTopology,
      },
      {
        type: 'button',
        label: 'Edit',
        onClick: handleEditTopology,
      },
      {
        type: 'button',
        label: 'Move',
        onClick: handleMoveTopology,
      },
      {
        type: 'button',
        label: 'Clone',
        onClick: () => handleCloneTopology(),
      },
      {
        type: 'button',
        label: 'Export',
        onClick: () => handleExportTopology(),
      },
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

// Handle topology actions from sidebar
const handleSidebarTopologyAction = async (topologyId: string, topologyName: string, action: string) => {
  sidebarActionTopologyId.value = topologyId
  sidebarActionTopologyName.value = topologyName

  switch (action) {
    case 'run':
      try {
        await runTopology(topologyId)
        showToast(`Topology "${topologyName}" run started`, 'success')
      } catch (error) {
        console.error('Failed to run topology:', error)
        showToast('Failed to run topology', 'error')
      }
      break
    case 'edit':
      editTopologyModalOpen.value = true
      break
    case 'move':
      moveTopologyModalOpen.value = true
      break
    case 'delete':
      deleteTopologyConfirmOpen.value = true
      break
    case 'clone':
      handleCloneTopology(topologyId)
      break
    case 'export':
      handleExportTopology(topologyId, topologyName)
      break
  }
}

// Initialize Flowbite dropdowns for folder actions
const initFolderDropdowns = () => {
  allFolders.value.forEach((folder) => {
    const dropdownElement = document.getElementById(`folderActionsDropdown-${folder.id}`)
    const buttonElement = document.getElementById(`folderActionsButton-${folder.id}`)

    if (dropdownElement && buttonElement) {
      new Dropdown(dropdownElement, buttonElement, {
        placement: 'bottom',
        triggerType: 'click',
        offsetSkidding: 0,
        offsetDistance: 10,
      })
    }
  })
}

// Refresh sidebar tree and allFolders
const refreshAfterCrud = async () => {
  try {
    allFolders.value = await fetchCategories()
  } catch (err) {
    console.error('Failed to reload categories:', err)
  }
  await sidebarRef.value?.refreshTree()

  // Re-initialize Flowbite dropdowns after DOM updates
  await nextTick()
  initFolderDropdowns()
}

// Folder dropdown actions
const handleFolderNewTopology = (folderId: string) => {
  activeFolderId.value = folderId
  newTopologyModalOpen.value = true
}

const handleFolderNewSubfolder = (folderId: string) => {
  activeFolderParentId.value = folderId
  newFolderModalOpen.value = true
}

const handleFolderRename = (folderId: string) => {
  const folder = allFolders.value.find(f => f.id === folderId)
  if (!folder) return
  activeFolderId.value = folderId
  activeFolderName.value = folder.name
  activeFolderParentId.value = folder.parentFolderId ?? null
  renameFolderModalOpen.value = true
}

const handleFolderDelete = (folderId: string) => {
  const folder = allFolders.value.find(f => f.id === folderId)
  if (!folder) return
  activeFolderId.value = folderId
  activeFolderName.value = folder.name
  deleteFolderConfirmOpen.value = true
}

const handleConfirmDelete = async () => {
  try {
    await deleteCategory(activeFolderId.value)
    showToast('Folder deleted successfully', 'success')
    await refreshAfterCrud()
  } catch (err) {
    console.error('Failed to delete folder:', err)
    showToast('Failed to delete folder', 'error')
  }
}

// "New Folder" button from sidebar header (root-level folder)
const handleOpenNewFolderModal = () => {
  activeFolderParentId.value = null
  newFolderModalOpen.value = true
}

// Handle topology selection from sidebar
const handleSelectTopology = (topologyId: string, topologyName: string, versionCount: number) => {
  selectedTopologyId.value = topologyId
  selectedTopologyName.value = topologyName

  // Always show modal to display version overview
  selectVersionModalOpen.value = true
}

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

    // Restore last active tab for this topology if it exists
    const lastTopology = getLastTopology()

    if (lastTopology && lastTopology.id === props.id && lastTopology.activeTab) {
      activeTopologyTab.value = lastTopology.activeTab

      // Load metrics if returning to metrics tab
      if (lastTopology.activeTab === 'metrics') {
        await loadMetrics()
      }
    } else {
      // Reset to first tab if this is a different topology
      activeTopologyTab.value = 'topology'
    }

    // Save last topology to localStorage (with current tab)
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

    // Check if description is truncated after render
    checkDescriptionTruncation()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load topology'
    console.error('Failed to load topology:', err)
  } finally {
    loading.value = false
  }
}


const handlePublish = async () => {
  if (!topology.value) return
  try {
    await publishTopology(topology.value._id)
    showToast('Topology published successfully', 'success')
    // Re-fetch detail so visibility updates and Publish button disappears
    topology.value = await fetchTopologyDetail(props.id, versionId.value)
    await sidebarRef.value?.refreshTree()
  } catch (error) {
    console.error('Failed to publish topology:', error)
    showToast('Failed to publish topology', 'error')
  }
}

const handleToggleEnabled = async () => {
  if (!topology.value) return
  const newEnabled = !topology.value.enabled
  try {
    await toggleTopologyEnabled(topology.value._id, newEnabled)
    showToast(`Topology ${newEnabled ? 'enabled' : 'disabled'} successfully`, 'success')
    topology.value = await fetchTopologyDetail(props.id, versionId.value)
    await sidebarRef.value?.refreshTree()
  } catch (error) {
    console.error('Failed to toggle topology:', error)
    showToast('Failed to toggle topology state', 'error')
  }
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
    await sidebarRef.value?.refreshTree()
  } else {
    topologyEditorRef.value?.reloadSchema()
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


// Access tab handlers
const handleAddGroup = (groupName: string) => {
  const newGroup: AccessGroup = {
    id: `group-${Date.now()}`,
    name: groupName,
    permission: 'user'
  }
  accessGroups.value.push(newGroup)
}

const handleRemoveGroup = (groupId: string) => {
  accessGroups.value = accessGroups.value.filter(g => g.id !== groupId)
}

const handlePermissionChange = (groupId: string, permission: 'manager' | 'developer' | 'user') => {
  const group = accessGroups.value.find(g => g.id === groupId)
  if (group) {
    group.permission = permission
  }
}

// Load metrics data
const { loadMappings } = useTopologyNodeMappings()

const loadMetrics = async () => {
  if (!props.id) return

  metricsLoading.value = true
  try {
    await loadMappings()
    metricsData.value = await fetchTopologyMetrics(props.id)
  } catch (err) {
    console.error('Failed to load metrics:', err)
  } finally {
    metricsLoading.value = false
  }
}

// Handle tab change
const handleTabChange = (tabId: string) => {
  activeTopologyTab.value = tabId

  // Load metrics data when switching to metrics tab
  if (tabId === 'metrics' && !metricsData.value) {
    loadMetrics()
  }

  // Save the active tab to localStorage
  if (topology.value) {
    setLastTopology({
      id: props.id,
      name: topology.value.name,
      versionId: versionId.value,
      activeTab: tabId
    })
  }
}

// Watch for changes in topology ID or version
watch(
  () => [props.id, versionId.value],
  async () => {
    await loadTopologyDetail()
  }
)

onMounted(async () => {
  await loadTopologyDetail()

  // Load categories from API
  try {
    allFolders.value = await fetchWithRetry(() => fetchCategories())
  } catch (error) {
    console.error('Failed to load categories:', error)
  }

  // Initialize Flowbite dropdowns
  setTimeout(() => {
    // Folder actions dropdowns
    initFolderDropdowns()

    // Add group dropdown
    const addGroupDropdown = document.getElementById('add-group-dropdown')
    const addGroupButton = document.querySelector('[data-dropdown-toggle="add-group-dropdown"]')
    if (addGroupDropdown && addGroupButton) {
      new Dropdown(addGroupDropdown, addGroupButton, {
        placement: 'bottom',
        triggerType: 'click',
        offsetSkidding: 0,
        offsetDistance: 10,
      })
    }
  }, 200)
})
</script>

<template>
  <div class="h-screen flex flex-col overflow-hidden bg-gray-50 dark:bg-gray-900">
    <AppNavbar />
    <div class="flex flex-1 overflow-hidden">
      <AppSidebar />
      <TopologiesSidebar
        ref="sidebarRef"
        v-model="topologySidebarCollapsed"
        @open-new-topology-modal="activeFolderId = null; newTopologyModalOpen = true"
        @open-new-folder-modal="handleOpenNewFolderModal"
        @select-topology="handleSelectTopology"
        @topology-action="handleSidebarTopologyAction"
      />

      <div id="main-content" class="flex-1 bg-gray-50 dark:bg-gray-900 overflow-hidden">
        <main class="relative h-full overflow-hidden">
          <div class="h-full overflow-y-auto">
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
            <div v-else-if="topology" class="px-4 pt-2 pb-4">
              <!-- Page Header -->
              <div class="mb-6">
                <!-- Top row: toggle button left, action buttons right -->
                <div class="flex items-center justify-between mb-2">
                  <!-- Toggle Sidebar Button -->
                   <div>
                    <button
                      type="button"
                      @click="topologySidebarCollapsed = !topologySidebarCollapsed"
                      class="items-center justify-center rounded-lg p-0 relative -left-1 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                    >
                      <!-- Open Sidebar Icon (when collapsed) -->
                      <svg
                        v-show="topologySidebarCollapsed"
                        class="w-6 h-6"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                        height="24px"
                        viewBox="0 -960 960 960"
                        width="24px"
                        fill="currentColor"
                      >
                        <path
                          d="M498.08-623.46v286.92L641.92-480 498.08-623.46ZM212.31-140q-29.92 0-51.12-21.19Q140-182.39 140-212.31v-535.38q0-29.92 21.19-51.12Q182.39-820 212.31-820h535.38q29.92 0 51.12 21.19Q820-777.61 820-747.69v535.38q0 29.92-21.19 51.12Q777.61-140 747.69-140H212.31ZM320-200v-560H212.31q-4.62 0-8.46 3.85-3.85 3.84-3.85 8.46v535.38q0 4.62 3.85 8.46 3.84 3.85 8.46 3.85H320Zm60 0h367.69q4.62 0 8.46-3.85 3.85-3.84 3.85-8.46v-535.38q0-4.62-3.85-8.46-3.84-3.85-8.46-3.85H380v560Zm-60 0H200h120Z"
                        />
                      </svg>
                      <!-- Close Sidebar Icon (when expanded) -->
                      <svg
                        v-show="!topologySidebarCollapsed"
                        class="w-6 h-6"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                        height="24px"
                        viewBox="0 -960 960 960"
                        width="24px"
                        fill="currentColor"
                      >
                        <path
                          d="M641.92-336.54v-286.92L498.08-480l143.84 143.46ZM212.31-140q-29.92 0-51.12-21.19Q140-182.39 140-212.31v-535.38q0-29.92 21.19-51.12Q182.39-820 212.31-820h535.38q29.92 0 51.12 21.19Q820-777.61 820-747.69v535.38q0 29.92-21.19 51.12Q777.61-140 747.69-140H212.31ZM320-200v-560H212.31q-4.62 0-8.46 3.85-3.85 3.84-3.85 8.46v535.38q0 4.62 3.85 8.46 3.84 3.85 8.46 3.85H320Zm60 0h367.69q4.62 0 8.46-3.85 3.85-3.84 3.85-8.46v-535.38q0-4.62-3.85-8.46-3.84-3.85-8.46-3.85H380v560Zm-60 0H200h120Z"
                        />
                      </svg>
                      <span class="sr-only">Toggle sidebar</span>
                    </button>

                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ topology.name }}</h1>
                    <!-- Description (truncated to 1 line) -->
                    <div v-if="topology.description" class="flex items-center gap-1 mt-1 max-w-xl">
                      <p data-description-text class="text-sm text-gray-500 dark:text-gray-400 overflow-hidden whitespace-nowrap">{{ topology.description }}</p>
                      <button
                        v-if="isDescriptionTruncated"
                        type="button"
                        @click="descriptionPopupOpen = true"
                        class="shrink-0 text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
                      >...</button>
                    </div>
                    <div class="flex items-center gap-2 mt-2">
                      <span class="text-sm text-gray-500 dark:text-gray-400">Version {{ topology.version }}</span>
                      <span :class="['text-xs font-medium px-2.5 py-0.5 rounded', statusBadgeClass]">
                        {{ statusLabel }}
                      </span>
                    </div>
                   </div>


                  <div class="flex items-center gap-2">
                    <Button variant="outline" @click="handleVersionsClick">
                      <svg class="w-5 h-5 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                        <path d="M480-120q-138 0-240.5-91.5T122-440h82q14 104 92.5 172T480-200q117 0 198.5-81.5T760-480q0-117-81.5-198.5T480-760q-69 0-129 32t-101 88h110v80H120v-240h80v94q51-64 124.5-99T480-840q75 0 140.5 28.5t114 77q48.5 48.5 77 114T840-480q0 75-28.5 140.5t-77 114q-48.5 48.5-114 77T480-120Zm112-192L440-464v-216h80v184l128 128-56 56Z"/>
                      </svg>
                      Versions
                    </Button>
                    <Button variant="outline" @click="handleOpenDesigner">
                      <svg class="w-5 h-5 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                        <path d="M160-120v-170l527-526q12-12 27-18t30-6q16 0 30.5 6t25.5 18l56 56q12 11 18 25.5t6 30.5q0 15-6 30t-18 27L330-120H160Zm80-80h56l393-392-28-29-29-28-392 393v56Zm560-503-57-57 57 57Zm-139 82-29-28 57 57-28-29ZM560-120q74 0 137-37t63-103q0-36-19-62t-51-45l-59 59q23 10 36 22t13 26q0 23-36.5 41.5T560-200q-17 0-28.5 11.5T520-160q0 17 11.5 28.5T560-120ZM183-426l60-60q-20-8-31.5-16.5T200-520q0-12 18-24t76-37q88-38 117-69t29-70q0-55-44-87.5T280-840q-45 0-80.5 16T145-785q-11 13-9 29t15 26q13 11 29 9t27-13q14-14 31-20t42-6q41 0 60.5 12t19.5 28q0 14-17.5 25.5T262-654q-80 35-111 63.5T120-520q0 32 17 54.5t46 39.5Z"/>
                      </svg>
                      Design
                    </Button>
                    <Button v-if="topology.visibility === 'draft'" @click="handlePublish">
                      <svg class="-ms-1 me-2 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M5 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h11.5c.07 0 .14-.007.207-.021.095.014.193.021.293.021h2a2 2 0 0 0 2-2V7a1 1 0 0 0-1-1h-1a1 1 0 1 0 0 2v11h-2V5a2 2 0 0 0-2-2H5Zm7 4a1 1 0 0 1 1-1h.5a1 1 0 1 1 0 2H13a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h.5a1 1 0 1 1 0 2H13a1 1 0 0 1-1-1Zm-6 4a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H7a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H7a1 1 0 0 1-1-1ZM7 6a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H7Zm1 3V8h1v1H8Z" clip-rule="evenodd"/>
                      </svg>
                      Publish
                    </Button>
                    <Button
                      v-if="topology.visibility !== 'draft'"
                      :variant="topology.enabled ? 'outline' : 'success'"
                      @click="handleToggleEnabled"
                    >
                      {{ topology.enabled ? 'Disable' : 'Enable' }}
                    </Button>
                    <MoreActions
                      id="topology-more-actions"
                      :sections="moreActionsSections"
                    />
                  </div>
                </div>

              </div>

              <!-- Tabs -->
              <TabsComponent
                :tabs="topologyTabs"
                :default-tab="activeTopologyTab"
                content-id="topology-tabs-content"
                @tab-change="handleTabChange"
              >
                <!-- Topology Tab Content -->
                <div id="topology-content" role="tabpanel" aria-labelledby="topology-tab">
                  <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg overflow-hidden">
                      <TopologyEditor ref="topologyEditorRef" :topology-id="topology._id" />
                    </div>
                  </div>
                </div>

                <!-- Context Tab Content -->
                <div id="context-content" role="tabpanel" aria-labelledby="context-tab" class="hidden">
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

                      <!-- Form Actions -->
                      <div class="pt-4">
                        <Button type="submit" :disabled="!isManifestValid || savingContext">
                          {{ savingContext ? 'Saving...' : 'Save' }}
                        </Button>
                      </div>
                    </form>
                  </TabCard>
                </div>

                <!-- Access Tab Content -->
                <div id="access-content" role="tabpanel" aria-labelledby="access-tab" class="hidden">
                  <TabCard>
                    <div class="flex items-center justify-between mb-6">
                      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Access Control</h3>
                      <Button
                        data-dropdown-toggle="add-group-dropdown"
                      >
                        <svg class="h-4 w-4 me-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Group
                      </Button>
                      <div id="add-group-dropdown" class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow w-60 dark:bg-gray-700">
                        <ul class="py-2 text-sm text-gray-700 dark:text-gray-200">
                          <li v-for="groupName in availableGroups" :key="groupName">
                            <button
                              type="button"
                              @click="handleAddGroup(groupName)"
                              class="block w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                            >
                              {{ groupName }}
                            </button>
                          </li>
                        </ul>
                      </div>
                    </div>

                    <div class="space-y-4">
                      <!-- Group cards -->
                      <div
                        v-for="group in accessGroups"
                        :key="group.id"
                        class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                      >
                        <div class="mb-4 flex items-center justify-between">
                          <h4 class="text-base font-semibold text-gray-900 dark:text-white">{{ group.name }}</h4>
                          <Button
                            variant="outline"
                            size="sm"
                            @click="handleRemoveGroup(group.id)"
                          >
                            <svg class="h-4 w-4 me-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M19 7H5m14 0-1 12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 7m14 0H5m3 0V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m-5 5v6m4-6v6" />
                            </svg>
                            Remove
                          </Button>
                        </div>
                        <div class="space-y-3">
                          <!-- Manager permission -->
                          <div class="flex items-start">
                            <div class="flex h-5 items-center">
                              <input
                                :id="`${group.id}-manager`"
                                :name="`${group.id}-permission`"
                                type="radio"
                                value="manager"
                                :checked="group.permission === 'manager'"
                                @change="handlePermissionChange(group.id, 'manager')"
                                class="h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                              >
                            </div>
                            <div class="ms-3 text-sm">
                              <label :for="`${group.id}-manager`" class="font-medium text-gray-900 dark:text-white">Manager</label>
                              <p class="text-xs text-gray-500 dark:text-gray-400">Full access including managing permissions, deleting topology, and all development features</p>
                            </div>
                          </div>
                          <!-- Developer permission -->
                          <div class="flex items-start">
                            <div class="flex h-5 items-center">
                              <input
                                :id="`${group.id}-developer`"
                                :name="`${group.id}-permission`"
                                type="radio"
                                value="developer"
                                :checked="group.permission === 'developer'"
                                @change="handlePermissionChange(group.id, 'developer')"
                                class="h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                              >
                            </div>
                            <div class="ms-3 text-sm">
                              <label :for="`${group.id}-developer`" class="font-medium text-gray-900 dark:text-white">Developer</label>
                              <p class="text-xs text-gray-500 dark:text-gray-400">Can edit topology configuration, manage nodes, and run processes</p>
                            </div>
                          </div>
                          <!-- User permission -->
                          <div class="flex items-start">
                            <div class="flex h-5 items-center">
                              <input
                                :id="`${group.id}-user`"
                                :name="`${group.id}-permission`"
                                type="radio"
                                value="user"
                                :checked="group.permission === 'user'"
                                @change="handlePermissionChange(group.id, 'user')"
                                class="h-4 w-4 border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                              >
                            </div>
                            <div class="ms-3 text-sm">
                              <label :for="`${group.id}-user`" class="font-medium text-gray-900 dark:text-white">User</label>
                              <p class="text-xs text-gray-500 dark:text-gray-400">View-only access with ability to run topology but cannot edit configuration</p>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </TabCard>
                </div>

                <!-- Processes Tab Content -->
                <div id="processes-content" role="tabpanel" aria-labelledby="processes-tab" class="hidden">
                  <TopologyProcessesTab
                    v-if="topology"
                    :topology-id="topology._id"
                    :topology-name="topology.name"
                  />
                </div>

                <!-- Logs Tab Content -->
                <div id="logs-content" role="tabpanel" aria-labelledby="logs-tab" class="hidden">
                  <TopologyLogsTab
                    v-if="topology"
                    :topology-id="topology._id"
                    :topology-name="topology.name"
                  />
                </div>

                <!-- Trash Tab Content -->
                <div id="trash-content" role="tabpanel" aria-labelledby="trash-tab" class="hidden">
                  <TopologyFailedMessagesTab
                    v-if="topology"
                    :topology-id="topology._id"
                    :topology-name="topology.name"
                  />
                </div>

                <!-- Metrics Tab Content -->
                <div id="metrics-content" role="tabpanel" aria-labelledby="metrics-tab" class="hidden">
                  <div v-if="metricsLoading" class="flex items-center justify-center py-12">
                    <div role="status">
                      <svg aria-hidden="true" class="w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-primary-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
                      </svg>
                      <span class="sr-only">Loading...</span>
                    </div>
                  </div>

                  <div v-else-if="metricsData" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Node Process Time Card -->
                    <Card>
                      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Node Process Time</h3>
                      <div class="mb-4 relative overflow-visible">
                        <NodeProcessTimeChart :data="metricsData.nodeProcessTimes" />
                      </div>
                    </Card>

                    <!-- Connector Request Time Card -->
                    <Card>
                      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Connector Request Time</h3>
                      <div class="mb-4 relative overflow-visible">
                        <ConnectorRequestTimeChart :data="metricsData.connectorRequestTimes" />
                      </div>
                    </Card>
                  </div>

                  <div v-else class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">No metrics data available</p>
                  </div>
                </div>
              </TabsComponent>
            </div>
          </div>
        </main>
      </div>
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

    <!-- Modals -->
    <NewTopologyModal v-model="newTopologyModalOpen" :category-id="activeFolderId" @created="refreshAfterCrud" />
    <NewFolderModal
      v-model="newFolderModalOpen"
      :parent-id="activeFolderParentId"
      @created="refreshAfterCrud"
    />
    <RenameFolderModal
      v-model="renameFolderModalOpen"
      :folder-id="activeFolderId"
      :folder-name="activeFolderName"
      :parent-id="activeFolderParentId"
      @renamed="refreshAfterCrud"
    />
    <SelectVersionModal
      v-model="selectVersionModalOpen"
      :topology-id="selectedTopologyId"
      :topology-name="selectedTopologyName"
    />


    <!-- Edit Topology Modal -->
    <EditTopologyModal
      v-model="editTopologyModalOpen"
      :topology-id="sidebarActionTopologyId"
      :topology-name="sidebarActionTopologyName"
      :current-description="sidebarActionTopologyId === topology?._id ? (topology?.description ?? '') : ''"
      @saved="handleTopologyEdited"
    />

    <!-- Move Topology Modal -->
    <MoveTopologyModal
      v-model="moveTopologyModalOpen"
      :topology-id="sidebarActionTopologyId"
      :topology-name="sidebarActionTopologyName"
      :current-category-id="sidebarActionTopologyId === topology?._id ? (topology?.category ?? null) : null"
      @moved="handleTopologyMoved"
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

    <!-- Delete Topology Confirm -->
    <Confirm
      v-model="deleteTopologyConfirmOpen"
      id="delete-topology-confirm"
      confirm-text="Yes, delete"
      @confirm="handleConfirmDeleteTopology"
    >
      <svg
        class="mx-auto mb-4 h-12 w-12 text-gray-400 dark:text-gray-200"
        aria-hidden="true"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 20 20"
      >
        <path
          stroke="currentColor"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
        />
      </svg>
      <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
        Are you sure you want to delete the topology "{{ sidebarActionTopologyName }}"?
      </h3>
    </Confirm>

    <!-- Coming Soon Confirm -->
    <Confirm
      v-model="comingSoonConfirmOpen"
      id="coming-soon-confirm"
      confirm-text="OK"
      cancel-text="Close"
      confirm-variant="primary"
      @confirm="comingSoonConfirmOpen = false"
    >
      <svg
        class="mx-auto mb-4 h-12 w-12 text-gray-400 dark:text-gray-200"
        aria-hidden="true"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
      >
        <path
          stroke="currentColor"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
        />
      </svg>
      <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
        {{ comingSoonFeature }} is coming soon...
      </h3>
    </Confirm>

    <!-- Delete Folder Confirm -->
    <Confirm
      v-model="deleteFolderConfirmOpen"
      id="delete-folder-confirm-detail"
      confirm-text="Yes, delete"
      @confirm="handleConfirmDelete"
    >
      <svg
        class="mx-auto mb-4 h-12 w-12 text-gray-400 dark:text-gray-200"
        aria-hidden="true"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 20 20"
      >
        <path
          stroke="currentColor"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
        />
      </svg>
      <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
        Are you sure you want to delete the folder "{{ activeFolderName }}"?
      </h3>
    </Confirm>

    <!-- Trace Drawer -->
    <TraceDrawer v-model="isTraceDrawerOpen" />

    <!-- Folder Actions Dropdowns (rendered at root level to avoid z-index issues) -->
    <div
      v-for="folder in allFolders"
      :key="'dropdown-' + folder.id"
      :id="`folderActionsDropdown-${folder.id}`"
      class="z-10 hidden w-44 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700"
    >
      <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400">
        <li>
          <button
            type="button"
            @click="handleFolderNewTopology(folder.id)"
            class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            New Topology
          </button>
        </li>
        <li>
          <button
            type="button"
            @click="handleFolderNewSubfolder(folder.id)"
            class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            New Folder
          </button>
        </li>
        <li>
          <button
            type="button"
            @click="handleFolderRename(folder.id)"
            class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            Rename Folder
          </button>
        </li>
        <li v-if="!nonEmptyFolderIds.has(folder.id)">
          <button
            type="button"
            @click="handleFolderDelete(folder.id)"
            class="inline-flex w-full items-center rounded-md px-3 py-2 text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600 dark:hover:text-red-400"
          >
            Delete Folder
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>

