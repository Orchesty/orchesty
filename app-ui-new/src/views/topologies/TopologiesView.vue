<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import AppSidebar from '@/components/layout/AppSidebar.vue'
import TopologiesSidebar from '@/components/topologies/TopologiesSidebar.vue'
import NewTopologyModal from '@/components/topologies/NewTopologyModal.vue'
import NewFolderModal from '@/components/topologies/NewFolderModal.vue'
import SelectVersionModal from '@/components/topologies/SelectVersionModal.vue'
import topologiesTreeData from '@/assets/mock-data/topologies-tree-data.json'
import type { TopologiesTreeNode, FolderItem } from '@/types/topologies-page'

const router = useRouter()

// Modal state
const newTopologyModalOpen = ref(false)
const newFolderModalOpen = ref(false)
const selectVersionModalOpen = ref(false)
const selectedTopologyId = ref('')
const selectedTopologyName = ref('')

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

// Handle topology selection
const handleSelectTopology = (topologyId: string, topologyName: string, versionCount: number) => {
  selectedTopologyId.value = topologyId
  selectedTopologyName.value = topologyName
  
  // Always show modal to display version overview
  selectVersionModalOpen.value = true
}

// Initialize Flowbite dropdowns for folder actions
onMounted(async () => {
  const { Dropdown } = await import('flowbite')
  
  // Initialize dropdown for each folder
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
            <!-- Placeholder content -->
            <div class="text-center py-12">
              <svg
                class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500"
                aria-hidden="true"
                height="24px"
                viewBox="0 -960 960 960"
                width="24px"
                fill="currentColor"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  d="M600-120v-120H440v-400h-80v120H80v-320h280v120h240v-120h280v320H600v-120h-80v320h80v-120h280v320H600Z"
                />
              </svg>
              <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                No topology selected
              </h3>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Select a topology from the sidebar to view its details
              </p>
            </div>
          </div>
        </main>
      </div>
    </div>
  </div>

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
</template>

