<script setup lang="ts">
import { ref, computed } from 'vue'
import type { TopologiesTreeNode, FolderItem, TopologyItem } from '@/types/topologies-page'

interface Props {
  item: TopologiesTreeNode
  level?: number
}

const props = withDefaults(defineProps<Props>(), {
  level: 0
})

const emit = defineEmits<{
  'select-topology': [topologyId: string, topologyName: string, versionCount: number]
  'folder-action': [folderId: string, action: string]
}>()

const isExpanded = ref(props.item.type === 'folder' ? (props.item as FolderItem).isExpanded : false)

const topologyItem = computed(() => props.item.type === 'topology' ? props.item as TopologyItem : null)

const toggleFolder = () => {
  if (props.item.type !== 'folder') return
  isExpanded.value = !isExpanded.value
}

const handleSelectTopology = () => {
  if (props.item.type === 'topology') {
    const versionCount = topologyItem.value?.versionCount || 1
    emit('select-topology', props.item.id, props.item.name, versionCount)
  }
}
</script>

<template>
  <!-- Folder Item -->
  <div v-if="item.type === 'folder'">
    <div class="flex items-center gap-1">
      <button
        type="button"
        @click="toggleFolder"
        class="flex-1 flex items-center gap-2 px-2 py-1.5 rounded text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
      >
        <svg
          :class="[
            'w-3 h-3 shrink-0 transition-transform duration-200',
            isExpanded ? 'rotate-90' : ''
          ]"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          width="24"
          height="24"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="m9 5 5 7-5 7"
          />
        </svg>
        <span class="truncate flex-1 text-left">{{ item.name }}</span>
      </button>
      <button
        type="button"
        :id="`folderActionsButton-${item.id}`"
        :data-dropdown-toggle="`folderActionsDropdown-${item.id}`"
        title="Folder actions"
        class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
      >
        <svg
          class="w-4 h-4"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-width="2"
            d="M6 12h.01m6 0h.01m5.99 0h.01"
          />
        </svg>
        <span class="sr-only">Actions</span>
      </button>
    </div>
    <div v-show="isExpanded" class="pl-6">
      <div class="space-y-1 mt-1">
        <TopologyTreeItem
          v-for="child in item.children"
          :key="child.id"
          :item="child"
          :level="level + 1"
          @select-topology="(id, name, versionCount) => emit('select-topology', id, name, versionCount)"
          @folder-action="(id, action) => emit('folder-action', id, action)"
        />
      </div>
    </div>
  </div>

  <!-- Topology Item -->
  <button
    v-else
    type="button"
    @click="handleSelectTopology"
    class="w-full flex items-center gap-2 px-2 py-1.5 rounded text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
  >
    <svg
      class="w-4 h-4 shrink-0"
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
    <span class="truncate">{{ item.name }}</span>
  </button>
</template>

