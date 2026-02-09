<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import TopologyTreeItem from './TopologyTreeItem.vue'
import type { TopologiesTreeNode } from '@/types/topologies-page'
import topologiesTreeData from '@/assets/mock-data/topologies-tree-data.json'

interface Props {
  modelValue?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: false
})

const emit = defineEmits<{
  'open-new-topology-modal': []
  'open-new-folder-modal': []
  'select-topology': [topologyId: string, topologyName: string]
  'update:modelValue': [value: boolean]
}>()

// Constants
const MIN_WIDTH = 256
const MAX_WIDTH = 512
const COLLAPSED_WIDTH = 0
const STORAGE_KEY_WIDTH = 'topologySidebarWidth'
const STORAGE_KEY_COLLAPSED = 'topologySidebarCollapsed'

// State
const isCollapsed = ref(props.modelValue)
const sidebarWidth = ref(MIN_WIDTH)
const treeData = ref<TopologiesTreeNode[]>(topologiesTreeData.data as TopologiesTreeNode[])

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
const handleSelectTopology = (topologyId: string, topologyName: string) => {
  emit('select-topology', topologyId, topologyName)
}

// Initialize sidebar state
onMounted(() => {
  const savedCollapsed = localStorage.getItem(STORAGE_KEY_COLLAPSED)
  if (savedCollapsed === 'true') {
    isCollapsed.value = true
    sidebarWidth.value = COLLAPSED_WIDTH
  } else {
    isCollapsed.value = false
    sidebarWidth.value = getSavedWidth()
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
        class="inline-flex items-center justify-center rounded-lg p-1 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
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
        class="inline-flex items-center justify-center rounded-lg p-1 text-sm font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
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
    </div>

    <!-- Scrollable Tree List -->
    <div
      id="topology-sidebar-scrollable"
      class="overflow-y-auto px-2 py-4 flex-1"
    >
      <div class="space-y-1">
        <TopologyTreeItem
          v-for="item in treeData"
          :key="item.id"
          :item="item"
          @select-topology="handleSelectTopology"
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

