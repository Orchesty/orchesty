<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import AppSidebar from '@/components/layout/AppSidebar.vue'
import TopologiesSidebar from '@/components/topologies/TopologiesSidebar.vue'
import VersionHistoryDrawer from '@/components/topologies/VersionHistoryDrawer.vue'
import TopologyDesignerDrawer from '@/components/topologies/TopologyDesignerDrawer.vue'
import NewTopologyModal from '@/components/topologies/NewTopologyModal.vue'
import NewFolderModal from '@/components/topologies/NewFolderModal.vue'
import SelectVersionModal from '@/components/topologies/SelectVersionModal.vue'
import Button from '@/components/ui/Button.vue'
import DropdownMenu from '@/components/ui/DropdownMenu.vue'
import TabsComponent, { type Tab } from '@/components/ui/Tabs.vue'
import { fetchTopologyDetail } from '@/services/topologiesService'
import type { TopologyDetail } from '@/types/topologies-page'
import topologiesTreeData from '@/assets/mock-data/topologies-tree-data.json'
import type { TopologiesTreeNode, FolderItem } from '@/types/topologies-page'
import { Dropdown } from 'flowbite'

interface Props {
  id: string
}

const props = defineProps<Props>()
const route = useRoute()
const router = useRouter()

const topology = ref<TopologyDetail | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)
const versionDrawerOpen = ref(false)
const designerDrawerOpen = ref(false)

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

onMounted(async () => {
  await loadTopologyDetail()
  
  // Initialize Flowbite dropdowns for folder actions
  setTimeout(() => {
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
              <TabsComponent :tabs="topologyTabs" content-id="topology-tabs-content">
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
                  <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Context content will be placed here</p>
                  </div>
                </div>
                
                <!-- Audit Tab Content -->
                <div id="audit-content" role="tabpanel" aria-labelledby="audit-tab" class="hidden">
                  <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Audit content will be placed here</p>
                  </div>
                </div>
                
                <!-- Access Tab Content -->
                <div id="access-content" role="tabpanel" aria-labelledby="access-tab" class="hidden">
                  <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Access content will be placed here</p>
                  </div>
                </div>
                
                <!-- Processes Tab Content -->
                <div id="processes-content" role="tabpanel" aria-labelledby="processes-tab" class="hidden">
                  <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Processes content will be placed here</p>
                  </div>
                </div>
                
                <!-- Logs Tab Content -->
                <div id="logs-content" role="tabpanel" aria-labelledby="logs-tab" class="hidden">
                  <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Logs content will be placed here</p>
                  </div>
                </div>
                
                <!-- Trash Tab Content -->
                <div id="trash-content" role="tabpanel" aria-labelledby="trash-tab" class="hidden">
                  <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Failed messages content will be placed here</p>
                  </div>
                </div>
                
                <!-- Metrics Tab Content -->
                <div id="metrics-content" role="tabpanel" aria-labelledby="metrics-tab" class="hidden">
                  <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Metrics content will be placed here</p>
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

