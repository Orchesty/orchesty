<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import TopologyTreeItem from './TopologyTreeItem.vue'
import type { TopologiesTreeNode } from '@/types/topologies-page'
import { fetchTopologiesTree } from '@/services/topologiesService'
import { useAuthorization } from '@/composables/useAuthorization'

interface Props {
  modelValue?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: false
})

const emit = defineEmits<{
  'open-new-topology-modal': []
  'open-new-folder-modal': []
  'open-import-topology-modal': []
  'select-topology': [topologyId: string, topologyName: string, versionCount: number]
  'topology-action': [topologyId: string, topologyName: string, action: string]
  'update:modelValue': [value: boolean]
}>()

// Constants
const MIN_WIDTH = 256
const MAX_WIDTH = 512
const COLLAPSED_WIDTH = 0
const STORAGE_KEY_WIDTH = 'topologySidebarWidth'
const STORAGE_KEY_COLLAPSED = 'topologySidebarCollapsed'

const { hasRole } = useAuthorization()

// State
const isCollapsed = ref(props.modelValue)
const sidebarWidth = ref(MIN_WIDTH)
const treeData = ref<TopologiesTreeNode[]>([])
const loadingTree = ref(false)
const treeError = ref(false)

const filteredTreeData = computed(() => {
  if (hasRole('system_manager')) return treeData.value
  return treeData.value.filter(
    node => !(node.type === 'folder' && 'system' in node && node.system),
  )
})

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

// Get saved width from localStorage
const getSavedWidth = (): number => {
  const saved = localStorage.getItem(STORAGE_KEY_WIDTH)
  return saved ? parseInt(saved, 10) : MIN_WIDTH
}

// Save width to localStorage
const saveWidth = (width: number) => {
  localStorage.setItem(STORAGE_KEY_WIDTH, width.toString())
}

// Toggle sidebar collapsed state
const toggleSidebar = () => {
  isCollapsed.value = !isCollapsed.value
  emit('update:modelValue', isCollapsed.value)

  if (isCollapsed.value) {
    sidebarWidth.value = COLLAPSED_WIDTH
  } else {
    sidebarWidth.value = getSavedWidth()
  }
}

// Resize functionality
let isResizing = false
let startX = 0
let startWidth = 0

const startResize = (e: MouseEvent) => {
  if (isCollapsed.value) return

  isResizing = true
  startX = e.clientX
  startWidth = sidebarWidth.value

  document.body.style.userSelect = 'none'
  document.body.style.cursor = 'col-resize'

  document.addEventListener('mousemove', handleMouseMove)
  document.addEventListener('mouseup', handleMouseUp)

  e.preventDefault()
}

const handleMouseMove = (e: MouseEvent) => {
  if (!isResizing) return

  const delta = e.clientX - startX
  const newWidth = Math.min(Math.max(startWidth + delta, MIN_WIDTH), MAX_WIDTH)

  sidebarWidth.value = newWidth
}

const handleMouseUp = () => {
  if (!isResizing) return

  isResizing = false
  document.body.style.userSelect = ''
  document.body.style.cursor = ''

  saveWidth(sidebarWidth.value)

  document.removeEventListener('mousemove', handleMouseMove)
  document.removeEventListener('mouseup', handleMouseUp)
}

// Handle topology selection
const handleSelectTopology = (topologyId: string, topologyName: string, versionCount: number) => {
  emit('select-topology', topologyId, topologyName, versionCount)
}

// Refresh tree data (called from parent after CRUD operations)
const refreshTree = async () => {
  loadingTree.value = true
  treeError.value = false
  try {
    treeData.value = await fetchWithRetry(() => fetchTopologiesTree())
  } catch (error) {
    console.error('Failed to reload topologies tree:', error)
    treeError.value = true
  } finally {
    loadingTree.value = false
  }
}

defineExpose({ refreshTree, treeData })

// Initialize sidebar state and load tree data
onMounted(async () => {
  const savedCollapsed = localStorage.getItem(STORAGE_KEY_COLLAPSED)
  if (savedCollapsed === 'true') {
    isCollapsed.value = true
    sidebarWidth.value = COLLAPSED_WIDTH
  } else {
    isCollapsed.value = false
    sidebarWidth.value = getSavedWidth()
  }

  // Load tree data from API
  loadingTree.value = true
  treeError.value = false
  try {
    treeData.value = await fetchWithRetry(() => fetchTopologiesTree())
  } catch (error) {
    console.error('Failed to load topologies tree:', error)
    treeError.value = true
  } finally {
    loadingTree.value = false
  }
})

// Watch and save collapsed state
watch(isCollapsed, (newValue) => {
  localStorage.setItem(STORAGE_KEY_COLLAPSED, newValue.toString())
})

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
  if (newValue !== isCollapsed.value) {
    isCollapsed.value = newValue
    if (isCollapsed.value) {
      sidebarWidth.value = COLLAPSED_WIDTH
    } else {
      sidebarWidth.value = getSavedWidth()
    }
  }
})
</script>

<template>
  <aside
    v-if="!isCollapsed"
    id="topology-sidebar"
    :style="{ width: sidebarWidth + 'px' }"
    class="relative border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex flex-col h-full transition-all duration-300"
  >
    <!-- Header with title -->
    <div
      id="topology-sidebar-header"
      class="pt-4 pl-4 pr-2"
    >
      <h1 class="text-xl font-bold text-gray-900 dark:text-white">Topologies</h1>
    </div>

    <!-- Action Buttons -->
    <div
      class="px-4 py-2 border-b border-gray-200 dark:border-gray-700 flex gap-2"
    >
      <!-- New Folder Button -->
      <button
        type="button"
        title="New Folder"
        @click="emit('open-new-folder-modal')"
        class="inline-flex items-center justify-center rounded-lg p-1 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg
          class="w-5 h-5"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          height="24px"
          viewBox="0 -960 960 960"
          width="24px"
          fill="currentColor"
        >
          <path
            d="M560-320h80v-80h80v-80h-80v-80h-80v80h-80v80h80v80ZM160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h240l80 80h320q33 0 56.5 23.5T880-640v400q0 33-23.5 56.5T800-160H160Zm0-80h640v-400H447l-80-80H160v480Zm0 0v-480 480Z"
          />
        </svg>
        <span class="sr-only">New Folder</span>
      </button>

      <!-- New Topology Button -->
      <button
        type="button"
        title="New Topology"
        @click="emit('open-new-topology-modal')"
        class="inline-flex items-center justify-center rounded-lg p-1 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg
          class="w-5 h-5"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          height="24px"
          viewBox="0 -960 960 960"
          width="24px"
          fill="currentColor"
        >
          <path
            d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h240v80H200v560h560v-240h80v240q0 33-23.5 56.5T760-120H200Zm440-400v-120H520v-80h120v-120h80v120h120v80H720v120h-80Z"
          />
        </svg>
        <span class="sr-only">New Topology</span>
      </button>

      <!-- Import Topology Button -->
      <button
        type="button"
        title="Import Topology"
        @click="emit('open-import-topology-modal')"
        class="inline-flex items-center justify-center rounded-lg p-1 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg
          class="w-5 h-5"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2"
        >
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
        </svg>
        <span class="sr-only">Import Topology</span>
      </button>
    </div>

    <!-- Scrollable Tree List -->
    <div
      id="topology-sidebar-scrollable"
      class="overflow-y-auto px-2 py-4 flex-1"
    >
      <!-- Loading state -->
      <div v-if="loadingTree" class="flex items-center justify-center py-8">
        <svg
          aria-hidden="true"
          class="w-6 h-6 text-gray-200 animate-spin dark:text-gray-600 fill-primary-600"
          viewBox="0 0 100 101"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
            fill="currentColor"
          />
          <path
            d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
            fill="currentFill"
          />
        </svg>
        <span class="sr-only">Loading...</span>
      </div>

      <!-- Error state -->
      <div v-else-if="treeError" class="flex flex-col items-center justify-center py-8 text-center px-4">
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">Failed to load topologies</p>
        <button
          type="button"
          @click="refreshTree"
          class="text-sm font-medium text-primary-600 hover:text-primary-700 hover:underline dark:text-primary-400 dark:hover:text-primary-300"
        >
          Try again
        </button>
      </div>

      <!-- Tree data -->
      <div v-else class="space-y-1">
        <TopologyTreeItem
          v-for="item in filteredTreeData"
          :key="item.id"
          :item="item"
          @select-topology="handleSelectTopology"
          @topology-action="(id, name, action) => emit('topology-action', id, name, action)"
        />
      </div>
    </div>

    <!-- Resize Handle -->
    <div
      id="topology-sidebar-resize-handle"
      @mousedown="startResize"
      class="absolute top-0 right-0 bottom-0 w-1 cursor-col-resize hover:bg-gray-400 dark:hover:bg-gray-600 transition-all"
      style="z-index: 10"
    />
  </aside>
</template>

