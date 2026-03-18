<script setup lang="ts">
import { ref, computed, nextTick, onMounted, provide } from 'vue'
import { useRouter, useRoute } from 'vue-router'
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
import { fetchCategories, deleteCategory, deleteTopology, cloneTopology, fetchTopologySchema } from '@/services/topologiesService'
import { useLastTopology } from '@/composables/useLastTopology'
import { useToast } from '@/composables/useToast'
import { Dropdown } from 'flowbite'
import type { TopologyLayoutContext } from '@/types/topologies-page'

const router = useRouter()
const route = useRoute()
const { showToast } = useToast()
const { getLastTopology, clearLastTopology } = useLastTopology()

const sidebarRef = ref<InstanceType<typeof TopologiesSidebar> | null>(null)

// Sidebar collapsed state
const getSidebarCollapsedFromStorage = (): boolean => {
  const saved = localStorage.getItem('topologySidebarCollapsed')
  return saved === 'true'
}
const topologySidebarCollapsed = ref(getSidebarCollapsedFromStorage())

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

// Folders loaded from API
const allFolders = ref<FolderItem[]>([])

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

// Handle topology selection from sidebar
const handleSelectTopology = (topologyId: string, topologyName: string, versionCount: number) => {
  if (versionCount <= 1) {
    router.push({ name: 'topology-detail', params: { id: topologyId } })
  } else {
    selectedTopologyId.value = topologyId
    selectedTopologyName.value = topologyName
    selectVersionModalOpen.value = true
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

const handleTopologyCreated = async (topologyId: string) => {
  await refreshAfterCrud()
  router.push({ name: 'topology-detail', params: { id: topologyId } })
}

const refreshAfterCrud = async () => {
  try {
    allFolders.value = await fetchCategories()
  } catch (error) {
    console.error('Failed to reload categories:', error)
  }
  await sidebarRef.value?.refreshTree()
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

const handleConfirmDeleteFolder = async () => {
  try {
    await deleteCategory(activeFolderId.value)
    showToast('Folder deleted successfully', 'success')
    await refreshAfterCrud()
  } catch (error) {
    console.error('Failed to delete folder:', error)
    showToast('Failed to delete folder', 'error')
  }
}

const handleOpenNewFolderModal = () => {
  activeFolderParentId.value = null
  newFolderModalOpen.value = true
}

// Shared topology action functions (used by sidebar AND detail view via provide/inject)
const actionTopologyDescription = ref('')

const openEditTopologyModal = (id: string, name: string, currentDescription?: string) => {
  actionTopologyId.value = id
  actionTopologyName.value = name
  actionTopologyDescription.value = currentDescription ?? ''
  editTopologyModalOpen.value = true
}

const actionTopologyCategoryId = ref<string | null>(null)

const openMoveTopologyModal = (id: string, name: string, currentCategoryId?: string | null) => {
  actionTopologyId.value = id
  actionTopologyName.value = name
  actionTopologyCategoryId.value = currentCategoryId ?? null
  moveTopologyModalOpen.value = true
}

const openDeleteTopologyConfirm = (id: string, name: string) => {
  actionTopologyId.value = id
  actionTopologyName.value = name
  deleteTopologyConfirmOpen.value = true
}

const handleCloneTopologyAction = async (id: string) => {
  try {
    const result = await cloneTopology(id)
    showToast('Topology cloned successfully', 'success')
    await refreshAfterCrud()
    router.push({ name: 'topology-detail', params: { id: result._id }, query: { version: result._id } })
  } catch (error) {
    console.error('Failed to clone topology:', error)
    showToast('Failed to clone topology', 'error')
  }
}

const handleExportTopologyAction = async (id: string, name: string) => {
  try {
    const schema = await fetchTopologySchema(id)
    const jsonString = JSON.stringify(schema, null, 4) + '\n'
    const blob = new Blob([jsonString], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `${name}.tplg.json`
    a.click()
    URL.revokeObjectURL(url)
    showToast(`Topology "${name}" exported successfully`, 'success')
  } catch (error) {
    console.error('Failed to export topology:', error)
    showToast('Failed to export topology', 'error')
  }
}

// Find a topology's folderId from the sidebar tree data
const findTopologyFolderId = (topologyId: string): string | null => {
  const tree: TopologiesTreeNode[] = sidebarRef.value?.treeData ?? []
  const search = (nodes: TopologiesTreeNode[], parentFolderId: string | null): string | null | undefined => {
    for (const node of nodes) {
      if (node.type === 'topology' && node.id === topologyId) {
        return node.folderId ?? parentFolderId
      }
      if (node.type === 'folder') {
        const found = search(node.children, node.id)
        if (found !== undefined) return found
      }
    }
    return undefined
  }
  return search(tree, null) ?? null
}

// Handle topology actions from sidebar
const handleSidebarTopologyAction = async (topologyId: string, topologyName: string, action: string) => {
  switch (action) {
    case 'edit':
      openEditTopologyModal(topologyId, topologyName)
      break
    case 'move':
      openMoveTopologyModal(topologyId, topologyName, findTopologyFolderId(topologyId))
      break
    case 'delete':
      openDeleteTopologyConfirm(topologyId, topologyName)
      break
  }
}

const handleConfirmDeleteTopology = async () => {
  if (!actionTopologyId.value) return
  const deletedId = actionTopologyId.value
  try {
    await deleteTopology(deletedId)
    showToast('Topology deleted successfully', 'success')
    deleteTopologyConfirmOpen.value = false

    const lastTopology = getLastTopology()
    if (lastTopology && lastTopology.id === deletedId) {
      clearLastTopology()
    }

    await refreshAfterCrud()

    // If currently viewing the deleted topology, navigate away
    if (route.params.id === deletedId) {
      router.push({ name: 'topologies' })
    }
  } catch (error) {
    console.error('Failed to delete topology:', error)
    showToast('Failed to delete topology', 'error')
  }
}

// Callbacks registered by child views to react to topology edit/move events
let topologyEditedCallback: (() => void) | null = null
let topologyMovedCallback: (() => void) | null = null

const onTopologyEdited = (callback: () => void) => {
  topologyEditedCallback = callback
}

const onTopologyMoved = (callback: () => void) => {
  topologyMovedCallback = callback
}

const handleTopologyEdited = async () => {
  await refreshAfterCrud()
  topologyEditedCallback?.()
}

const handleTopologyMoved = async () => {
  await refreshAfterCrud()
  topologyMovedCallback?.()
}

// Provide shared context to child views (TopologyDetailView)
const layoutContext: TopologyLayoutContext = {
  openEditTopologyModal,
  openMoveTopologyModal,
  openDeleteTopologyConfirm,
  handleCloneTopologyAction,
  handleExportTopologyAction,
  refreshSidebar: refreshAfterCrud,
  onTopologyEdited,
  onTopologyMoved,
  sidebarRef,
  topologySidebarCollapsed,
}

provide('topologyLayout', layoutContext)

// On mount: load folders, init dropdowns, auto-redirect to last topology
onMounted(async () => {
  try {
    allFolders.value = await fetchCategories()
  } catch (error) {
    console.error('Failed to load categories:', error)
  }

  await nextTick()
  setTimeout(() => initFolderDropdowns(), 100)

  // Auto-redirect to last topology if on placeholder route
  if (route.name === 'topologies') {
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
        v-model="topologySidebarCollapsed"
        @open-new-topology-modal="activeFolderId = ''; newTopologyModalOpen = true"
        @open-new-folder-modal="handleOpenNewFolderModal"
        @select-topology="handleSelectTopology"
        @topology-action="handleSidebarTopologyAction"
      />

      <div id="main-content" class="flex-1 bg-gray-50 dark:bg-gray-900 overflow-hidden">
        <main class="relative h-full overflow-hidden">
          <div class="h-full overflow-y-auto">
            <RouterView />
          </div>
        </main>
      </div>
    </div>
  </div>

  <!-- Modals -->
  <NewTopologyModal v-model="newTopologyModalOpen" :category-id="activeFolderId" @created="handleTopologyCreated" />
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
  <EditTopologyModal
    v-model="editTopologyModalOpen"
    :topology-id="actionTopologyId"
    :topology-name="actionTopologyName"
    :current-description="actionTopologyDescription"
    @saved="handleTopologyEdited"
  />
  <MoveTopologyModal
    v-model="moveTopologyModalOpen"
    :topology-id="actionTopologyId"
    :topology-name="actionTopologyName"
    :current-category-id="actionTopologyCategoryId"
    @moved="handleTopologyMoved"
  />

  <!-- Delete Folder Confirm -->
  <Confirm
    v-model="deleteFolderConfirmOpen"
    id="delete-folder-confirm"
    confirm-text="Yes, delete"
    @confirm="handleConfirmDeleteFolder"
  >
    <svg class="mx-auto mb-4 h-12 w-12 text-gray-400 dark:text-gray-200" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
    <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
      Are you sure you want to delete the folder "{{ activeFolderName }}"?
    </h3>
  </Confirm>

  <!-- Delete Topology Confirm -->
  <Confirm
    v-model="deleteTopologyConfirmOpen"
    id="delete-topology-confirm"
    confirm-text="Yes, delete"
    @confirm="handleConfirmDeleteTopology"
  >
    <svg class="mx-auto mb-4 h-12 w-12 text-gray-400 dark:text-gray-200" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
    <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
      Are you sure you want to delete the topology "{{ actionTopologyName }}" including all its versions?
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
    <svg class="mx-auto mb-4 h-12 w-12 text-gray-400 dark:text-gray-200" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
    <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
      {{ comingSoonFeature }} is coming soon...
    </h3>
  </Confirm>

  <!-- Folder Actions Dropdowns -->
  <div
    v-for="folder in allFolders"
    :key="'dropdown-' + folder.id"
    :id="`folderActionsDropdown-${folder.id}`"
    class="z-10 hidden w-44 divide-y divide-gray-100 rounded-lg bg-white shadow-sm dark:divide-gray-600 dark:bg-gray-700"
  >
    <ul class="p-2 text-sm font-medium text-gray-500 dark:text-gray-400">
      <li>
        <button type="button" @click="handleFolderNewTopology(folder.id)" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
          New Topology
        </button>
      </li>
      <li>
        <button type="button" @click="handleFolderNewSubfolder(folder.id)" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
          New Folder
        </button>
      </li>
      <li>
        <button type="button" @click="handleFolderRename(folder.id)" class="inline-flex w-full items-center rounded-md px-3 py-2 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white">
          Rename Folder
        </button>
      </li>
      <li v-if="!nonEmptyFolderIds.has(folder.id)">
        <button type="button" @click="handleFolderDelete(folder.id)" class="inline-flex w-full items-center rounded-md px-3 py-2 text-red-600 hover:bg-gray-100 dark:text-red-500 dark:hover:bg-gray-600 dark:hover:text-red-400">
          Delete Folder
        </button>
      </li>
    </ul>
  </div>
</template>
