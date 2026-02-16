<script setup lang="ts">
import { ref, computed, nextTick, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import AppSidebar from '@/components/layout/AppSidebar.vue'
import TopologiesSidebar from '@/components/topologies/TopologiesSidebar.vue'
import NewTopologyModal from '@/components/topologies/NewTopologyModal.vue'
import NewFolderModal from '@/components/topologies/NewFolderModal.vue'
import RenameFolderModal from '@/components/topologies/RenameFolderModal.vue'
import SelectVersionModal from '@/components/topologies/SelectVersionModal.vue'
import MoveTopologyModal from '@/components/topologies/MoveTopologyModal.vue'
import EditTopologyModal from '@/components/topologies/EditTopologyModal.vue'
import Confirm from '@/components/ui/Confirm.vue'
import type { FolderItem, TopologiesTreeNode } from '@/types/topologies-page'
import { fetchCategories, deleteCategory, deleteTopology, runTopology } from '@/services/topologiesService'
import { useLastTopology } from '@/composables/useLastTopology'
import { useToast } from '@/composables/useToast'
import { Dropdown } from 'flowbite'

const router = useRouter()
const { showToast } = useToast()
const { getLastTopology, clearLastTopology } = useLastTopology()

// Sidebar ref for refreshTree
const sidebarRef = ref<InstanceType<typeof TopologiesSidebar> | null>(null)

// Modal state
const newTopologyModalOpen = ref(false)
const newFolderModalOpen = ref(false)
const renameFolderModalOpen = ref(false)
const deleteFolderConfirmOpen = ref(false)
const selectVersionModalOpen = ref(false)
const selectedTopologyId = ref('')
const selectedTopologyName = ref('')

// Topology action modals
const editTopologyModalOpen = ref(false)
const moveTopologyModalOpen = ref(false)
const deleteTopologyConfirmOpen = ref(false)
const comingSoonConfirmOpen = ref(false)
const comingSoonFeature = ref('')
const actionTopologyId = ref('')
const actionTopologyName = ref('')

// Active folder for CRUD operations
const activeFolderId = ref('')
const activeFolderName = ref('')
const activeFolderParentId = ref<string | null>(null)

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

// Handle topology selection
const handleSelectTopology = (topologyId: string, topologyName: string, versionCount: number) => {
  selectedTopologyId.value = topologyId
  selectedTopologyName.value = topologyName

  // Always show modal to display version overview
  selectVersionModalOpen.value = true
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
  } catch (error) {
    console.error('Failed to reload categories:', error)
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
  } catch (error) {
    console.error('Failed to delete folder:', error)
    showToast('Failed to delete folder', 'error')
  }
}

// Handle topology actions from sidebar
const handleSidebarTopologyAction = async (topologyId: string, topologyName: string, action: string) => {
  actionTopologyId.value = topologyId
  actionTopologyName.value = topologyName

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
      comingSoonFeature.value = 'Clone'
      comingSoonConfirmOpen.value = true
      break
    case 'export':
      comingSoonFeature.value = 'Export'
      comingSoonConfirmOpen.value = true
      break
  }
}

const handleConfirmDeleteTopology = async () => {
  if (!actionTopologyId.value) return
  try {
    await deleteTopology(actionTopologyId.value)
    showToast('Topology deleted successfully', 'success')
    deleteTopologyConfirmOpen.value = false

    // Clear lastTopology if the deleted topology was the last viewed one
    const lastTopology = getLastTopology()
    if (lastTopology && lastTopology.id === actionTopologyId.value) {
      clearLastTopology()
    }

    await refreshAfterCrud()
  } catch (error) {
    console.error('Failed to delete topology:', error)
    showToast('Failed to delete topology', 'error')
  }
}

const handleTopologyEdited = async () => {
  await refreshAfterCrud()
}

const handleTopologyMoved = async () => {
  await refreshAfterCrud()
}

// "New Folder" button from sidebar header (root-level folder)
const handleOpenNewFolderModal = () => {
  activeFolderParentId.value = null
  newFolderModalOpen.value = true
}

// Initialize folders and Flowbite dropdowns
onMounted(async () => {
  // Load folders from API
  try {
    allFolders.value = await fetchCategories()
  } catch (error) {
    console.error('Failed to load categories:', error)
  }

  // Initialize Flowbite dropdowns
  await nextTick()
  setTimeout(() => initFolderDropdowns(), 100)

  // Automatically redirect to last opened topology
  const lastTopology = getLastTopology()
  if (lastTopology) {
    const query = lastTopology.versionId
      ? { version: lastTopology.versionId }
      : undefined

    router.push({
      name: 'topology-detail',
      params: { id: lastTopology.id },
      query
    })
  }
})
</script>

<template>
  <div class="h-screen flex flex-col overflow-hidden bg-gray-50 dark:bg-gray-900">
    <AppNavbar />
    <div class="flex flex-1 overflow-hidden">
      <AppSidebar />
      <TopologiesSidebar
        ref="sidebarRef"
        @open-new-topology-modal="activeFolderId = null; newTopologyModalOpen = true"
        @open-new-folder-modal="handleOpenNewFolderModal"
        @select-topology="handleSelectTopology"
        @topology-action="handleSidebarTopologyAction"
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

  <!-- Delete Folder Confirm -->
  <Confirm
    v-model="deleteFolderConfirmOpen"
    id="delete-folder-confirm"
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

  <!-- Topology Action Modals -->
  <EditTopologyModal
    v-model="editTopologyModalOpen"
    :topology-id="actionTopologyId"
    :topology-name="actionTopologyName"
    current-description=""
    @saved="handleTopologyEdited"
  />
  <MoveTopologyModal
    v-model="moveTopologyModalOpen"
    :topology-id="actionTopologyId"
    :topology-name="actionTopologyName"
    @moved="handleTopologyMoved"
  />

  <!-- Delete Topology Confirm -->
  <Confirm
    v-model="deleteTopologyConfirmOpen"
    id="delete-topology-confirm-list"
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
      Are you sure you want to delete the topology "{{ actionTopologyName }}"?
    </h3>
  </Confirm>

  <!-- Coming Soon Confirm -->
  <Confirm
    v-model="comingSoonConfirmOpen"
    id="coming-soon-confirm-list"
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
</template>
