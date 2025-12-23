<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
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
import NewTopologyModal from '@/components/topologies/NewTopologyModal.vue'
import NewFolderModal from '@/components/topologies/NewFolderModal.vue'
import SelectVersionModal from '@/components/topologies/SelectVersionModal.vue'
import Button from '@/components/ui/Button.vue'
import DropdownMenu from '@/components/ui/DropdownMenu.vue'
import TabsComponent, { type Tab } from '@/components/ui/Tabs.vue'
import Card from '@/components/ui/Card.vue'
import Textarea from '@/components/ui/datagrid/Textarea.vue'
import { fetchTopologyDetail } from '@/services/topologiesService'
import { fetchTopologyMetrics } from '@/services/topologyMetricsService'
import type { TopologyDetail } from '@/types/topologies-page'
import type { TopologyMetrics } from '@/types/topology-metrics'
import topologiesTreeData from '@/assets/mock-data/topologies-tree-data.json'
import type { TopologiesTreeNode, FolderItem } from '@/types/topologies-page'
import { Dropdown } from 'flowbite'
import { useLastTopology } from '@/composables/useLastTopology'

interface Props {
  id: string
}

const props = defineProps<Props>()
const route = useRoute()

const { setLastTopology, getLastTopology } = useLastTopology()

const topology = ref<TopologyDetail | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)
const versionDrawerOpen = ref(false)
const designerDrawerOpen = ref(false)

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
const contextManifest = ref(`configuration.api_key
configuration.api_url
user.email
order.status`)

// Audit tab state
interface AuditEntity {
  id: string
  name: string
  attributes: Array<{ key: string; description: string }>
}

const selectedEntity = ref<string | null>('entity-2')

const auditEntities = computed<AuditEntity[]>(() => [
  {
    id: 'entity-1',
    name: 'Customer account',
    attributes: [
      { key: 'customer_id', description: 'Unique customer identifier' },
      { key: 'account_status', description: 'Current status of the account' }
    ]
  },
  {
    id: 'entity-2',
    name: 'Marketing platform',
    attributes: [
      { key: 'account_id', description: 'Unique identifier of the external account' },
      { key: 'api_token', description: 'Token used to authenticate API calls' }
    ]
  },
  {
    id: 'entity-3',
    name: 'Operations monitor',
    attributes: [
      { key: 'monitor_id', description: 'Identifier for the monitoring system' },
      { key: 'alert_endpoint', description: 'URL endpoint for receiving alerts' }
    ]
  }
])

const currentEntity = computed(() => {
  if (!selectedEntity.value) return null
  return auditEntities.value.find(e => e.id === selectedEntity.value) || null
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
  { id: 'audit', label: 'Audit', target: 'audit-content' },
  { id: 'access', label: 'Access', target: 'access-content' },
  { id: 'processes', label: 'Processes', target: 'processes-content' },
  { id: 'logs', label: 'Logs', target: 'logs-content' },
  { id: 'trash', label: 'Failed Messages', target: 'trash-content' },
  { id: 'metrics', label: 'Metrics', target: 'metrics-content' }
]

// Sidebar modal state
const newTopologyModalOpen = ref(false)
const newFolderModalOpen = ref(false)
const selectVersionModalOpen = ref(false)
const selectedTopologyId = ref('')
const selectedTopologyName = ref('')

const versionId = computed(() => route.query.version as string | undefined)

const currentVersionId = computed(() => {
  if (!topology.value) return ''
  // Find the version that matches the current topology version
  const currentVersion = topology.value.versions.find(v => v.version === topology.value!.version)
  return currentVersion?.id || ''
})

// Get all folders from tree data for dropdown initialization
const allFolders = computed(() => {
  const folders: FolderItem[] = []
  
  const extractFolders = (nodes: TopologiesTreeNode[]) => {
    for (const node of nodes) {
      if (node.type === 'folder') {
        folders.push(node as FolderItem)
        if (node.children && node.children.length > 0) {
          extractFolders(node.children)
        }
      }
    }
  }
  
  extractFolders(topologiesTreeData.data as TopologiesTreeNode[])
  return folders
})

const statusBadgeClass = computed(() => {
  if (!topology.value) return ''
  if (topology.value.visibility === 'draft') {
    return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
  }
  if (topology.value.status === 'Running') {
    return 'bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-300'
  }
  return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
})

const statusLabel = computed(() => {
  if (!topology.value) return ''
  return topology.value.visibility === 'draft' ? 'Draft' : topology.value.status
})

const moreActionsItems = [
  {
    type: 'link' as const,
    label: 'Edit',
    icon: 'M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z',
    action: () => console.log('Edit topology')
  },
  {
    type: 'link' as const,
    label: 'Move',
    icon: 'M806-440H320v-80h486l-62-62 56-58 160 160-160 160-56-58 62-62ZM600-600v-160H200v560h400v-160h80v160q0 33-23.5 56.5T600-120H200q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h400q33 0 56.5 23.5T680-760v160h-80Z',
    action: () => console.log('Move topology')
  },
  {
    type: 'link' as const,
    label: 'Export',
    icon: 'M13 11.15V4a1 1 0 1 0-2 0v7.15L8.78 8.374a1 1 0 1 0-1.56 1.25l4 5a1 1 0 0 0 1.56 0l4-5a1 1 0 1 0-1.56-1.25L13 11.15Z M9.657 15.874 7.358 13H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2.358l-2.3 2.874a3 3 0 0 1-4.685 0ZM17 16a1 1 0 1 0 0 2h.01a1 1 0 1 0 0-2H17Z',
    action: () => console.log('Export topology')
  },
  {
    type: 'divider' as const
  },
  {
    type: 'link' as const,
    label: 'Delete',
    icon: 'M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z',
    danger: true,
    action: () => console.log('Delete topology')
  }
]

// Handle topology selection from sidebar
const handleSelectTopology = (topologyId: string, topologyName: string, versionCount: number) => {
  selectedTopologyId.value = topologyId
  selectedTopologyName.value = topologyName
  
  // Always show modal to display version overview
  selectVersionModalOpen.value = true
}

const loadTopologyDetail = async () => {
  loading.value = true
  error.value = null
  try {
    topology.value = await fetchTopologyDetail(props.id, versionId.value)
    
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
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load topology'
    console.error('Failed to load topology:', err)
  } finally {
    loading.value = false
  }
}


const handlePublish = () => {
  console.log('Publish topology')
  // TODO: Implement publish functionality
}

const handleVersionsClick = () => {
  versionDrawerOpen.value = true
}

const handleOpenDesigner = () => {
  designerDrawerOpen.value = true
}

const handleSaveDesign = () => {
  console.log('Save topology design')
  // TODO: Implement save logic
}

// Context tab handlers
const handleSaveContext = () => {
  console.log('Save context manifest:', contextManifest.value)
  // TODO: Implement save logic
}

// Audit tab handlers
const handleSelectEntity = (entityId: string) => {
  selectedEntity.value = entityId
}

const handleCreateNewEntity = () => {
  console.log('Create new audit entity')
  // TODO: Open modal for creating new entity
}

const handleEditEntity = () => {
  console.log('Edit entity:', currentEntity.value)
  // TODO: Open modal for editing entity
}

const handleSaveAudit = () => {
  console.log('Save audit entity:', selectedEntity.value)
  // TODO: Implement save logic
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
const loadMetrics = async () => {
  if (!props.id) return
  
  metricsLoading.value = true
  try {
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
  
  // Initialize Flowbite dropdowns
  setTimeout(() => {
    // Folder actions dropdowns
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
    
    // Audit entity dropdown
    const auditEntityDropdown = document.getElementById('audit-entity-dropdown')
    const auditEntityButton = document.querySelector('[data-dropdown-toggle="audit-entity-dropdown"]')
    if (auditEntityDropdown && auditEntityButton) {
      new Dropdown(auditEntityDropdown, auditEntityButton, {
        placement: 'bottom-start',
        triggerType: 'click',
        offsetSkidding: 0,
        offsetDistance: 10,
      })
    }
    
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
        @open-new-topology-modal="newTopologyModalOpen = true"
        @open-new-folder-modal="newFolderModalOpen = true"
        @select-topology="handleSelectTopology"
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
            <div v-else-if="topology" class="px-4 pt-6 pb-4">
              <!-- Page Header -->
              <div class="mb-6">
                <div class="flex items-center justify-between">
                  <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ topology.name }}</h1>
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
                    <Button @click="handlePublish">
                      <svg class="-ms-1 me-2 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M5 3a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h11.5c.07 0 .14-.007.207-.021.095.014.193.021.293.021h2a2 2 0 0 0 2-2V7a1 1 0 0 0-1-1h-1a1 1 0 1 0 0 2v11h-2V5a2 2 0 0 0-2-2H5Zm7 4a1 1 0 0 1 1-1h.5a1 1 0 1 1 0 2H13a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h.5a1 1 0 1 1 0 2H13a1 1 0 0 1-1-1Zm-6 4a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H7a1 1 0 0 1-1-1Zm0 3a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H7a1 1 0 0 1-1-1ZM7 6a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1V7a1 1 0 0 0-1-1H7Zm1 3V8h1v1H8Z" clip-rule="evenodd"/>
                      </svg>
                      Publish
                    </Button>
                    <DropdownMenu
                      dropdown-id="topology-more-dropdown"
                      :items="moreActionsItems"
                      button-class="inline-flex items-center rounded-lg p-2 text-center text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                    >
                      <template #button-content>
                        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 3">
                          <path d="M2 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Zm6.041 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM14 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3Z"/>
                        </svg>
                        <span class="sr-only">More actions</span>
                      </template>
                    </DropdownMenu>
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
                    <div class="relative h-[500px] bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg flex items-center justify-center">
                      <button
                        type="button"
                        @click="handleOpenDesigner"
                        class="absolute top-4 right-4 inline-flex items-center rounded-lg px-3 py-2 text-center text-sm font-medium text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
                      >
                        <svg class="w-5 h-5 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
                          <path d="M160-120v-170l527-526q12-12 27-18t30-6q16 0 30.5 6t25.5 18l56 56q12 11 18 25.5t6 30.5q0 15-6 30t-18 27L330-120H160Zm80-80h56l393-392-28-29-29-28-392 393v56Zm560-503-57-57 57 57Zm-139 82-29-28 57 57-28-29ZM560-120q74 0 137-37t63-103q0-36-19-62t-51-45l-59 59q23 10 36 22t13 26q0 23-36.5 41.5T560-200q-17 0-28.5 11.5T520-160q0 17 11.5 28.5T560-120ZM183-426l60-60q-20-8-31.5-16.5T200-520q0-12 18-24t76-37q88-38 117-69t29-70q0-55-44-87.5T280-840q-45 0-80.5 16T145-785q-11 13-9 29t15 26q13 11 29 9t27-13q14-14 31-20t42-6q41 0 60.5 12t19.5 28q0 14-17.5 25.5T262-654q-80 35-111 63.5T120-520q0 32 17 54.5t46 39.5Z"/>
                        </svg>
                        Design
                      </button>
                      <p class="text-sm text-gray-400 dark:text-gray-500">bpmn.io component will be placed here</p>
                    </div>
                  </div>
                </div>
                
                <!-- Context Tab Content -->
                <div id="context-content" role="tabpanel" aria-labelledby="context-tab" class="hidden">
                  <Card>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">MCP Manifest</h3>
                    
                    <form @submit.prevent="handleSaveContext" class="space-y-6">
                      <div>
                        <Textarea
                          v-model="contextManifest"
                          placeholder="Enter manifest text (each line = array element)"
                          :rows="8"
                        />
                      </div>

                      <!-- Form Actions -->
                      <div class="pt-4">
                        <Button type="submit">
                          Save
                        </Button>
                      </div>
                    </form>
                  </Card>
                </div>
                
                <!-- Audit Tab Content -->
                <div id="audit-content" role="tabpanel" aria-labelledby="audit-tab" class="hidden">
                  <Card>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Audit Entity</h3>
                    
                    <form @submit.prevent="handleSaveAudit" class="space-y-6">
                      <!-- Section: Entity selection -->
                      <div class="space-y-4">
                        <div>
                          <div class="flex flex-wrap items-center gap-3">
                            <button
                              type="button"
                              data-dropdown-toggle="audit-entity-dropdown"
                              class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-primary-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-primary-900"
                            >
                              <span class="me-2">Select entity</span>
                              <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
                              </svg>
                            </button>
                          </div>
                        </div>

                        <div
                          id="audit-entity-dropdown"
                          class="z-10 hidden w-60 divide-y divide-gray-100 rounded-lg bg-white shadow dark:bg-gray-700"
                          data-dropdown-placement="bottom-start"
                        >
                          <ul class="py-2 text-sm text-gray-700 dark:text-gray-200">
                            <li v-for="entity in auditEntities" :key="entity.id">
                              <button
                                type="button"
                                @click="handleSelectEntity(entity.id)"
                                class="block w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                              >
                                {{ entity.name }}
                              </button>
                            </li>
                          </ul>
                          <div class="py-1">
                            <button
                              type="button"
                              @click="handleCreateNewEntity"
                              class="block w-full px-4 py-2 text-left text-sm font-semibold text-primary-600 hover:bg-gray-100 dark:text-primary-400 dark:hover:bg-gray-600"
                            >
                              + Create new
                            </button>
                          </div>
                        </div>

                        <!-- Section: Selected entity -->
                        <div v-if="currentEntity" class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                          <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                              <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 uppercase">entity name</label>
                              <h4 class="text-base font-semibold text-gray-900 dark:text-white">{{ currentEntity.name }}</h4>
                              <div class="mt-4">
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1 uppercase">attributes</label>
                                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                                  <div
                                    v-for="attr in currentEntity.attributes"
                                    :key="attr.key"
                                    class="flex flex-col gap-0.5 sm:flex-row sm:items-center sm:gap-2"
                                  >
                                    <span class="font-medium text-gray-900 dark:text-white">{{ attr.key }}:</span>
                                    <span class="text-gray-600 dark:text-gray-300">{{ attr.description }}</span>
                                  </div>
                                </div>
                              </div>
                            </div>
                            <Button
                              variant="outline"
                              type="button"
                              @click="handleEditEntity"
                            >
                              <svg class="h-4 w-4 me-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.25 2.25 0 0 1 3.182 3.182l-9.8 9.8a4.5 4.5 0 0 1-1.897 1.128l-3.356.957a.75.75 0 0 1-.918-.918l.957-3.356a4.5 4.5 0 0 1 1.128-1.897l9.8-9.8Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5 13.5 4.5" />
                              </svg>
                              Edit
                            </Button>
                          </div>
                        </div>
                      </div>

                      <!-- Form Actions -->
                      <div class="pt-4">
                        <Button type="submit">
                          Save
                        </Button>
                      </div>
                    </form>
                  </Card>
                </div>
                
                <!-- Access Tab Content -->
                <div id="access-content" role="tabpanel" aria-labelledby="access-tab" class="hidden">
                  <Card>
                    <div class="flex items-center justify-between mb-6">
                      <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Access Control</h3>
                      <button
                        type="button"
                        data-dropdown-toggle="add-group-dropdown"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
                      >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        Group
                      </button>
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
                          <button
                            type="button"
                            @click="handleRemoveGroup(group.id)"
                            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-primary-900"
                          >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                              <path stroke-linecap="round" stroke-linejoin="round" d="M19 7H5m14 0-1 12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 7m14 0H5m3 0V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m-5 5v6m4-6v6" />
                            </svg>
                            Remove
                          </button>
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
                  </Card>
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
      :current-version-id="currentVersionId"
      placement="right"
    />

    <!-- Topology Designer Drawer -->
    <TopologyDesignerDrawer
      v-if="topology"
      v-model="designerDrawerOpen"
      :topology-name="topology.name"
      :topology-version="topology.version"
      @save="handleSaveDesign"
    />

    <!-- Modals -->
    <NewTopologyModal v-model="newTopologyModalOpen" />
    <NewFolderModal v-model="newFolderModalOpen" />
    <SelectVersionModal
      v-model="selectVersionModalOpen"
      :topology-id="selectedTopologyId"
      :topology-name="selectedTopologyName"
    />

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
            @click="newTopologyModalOpen = true"
            class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            New Topology
          </button>
        </li>
        <li>
          <button
            type="button"
            @click="newFolderModalOpen = true"
            class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            New Folder
          </button>
        </li>
        <li>
          <button
            type="button"
            class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
          >
            Rename Folder
          </button>
        </li>
        <li>
          <button
            type="button"
            class="inline-flex w-full items-center rounded-md px-3 py-2 text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600 dark:hover:text-red-400"
          >
            Delete Folder
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>

