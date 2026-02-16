<script setup lang="ts">
// Recursive tree node for folder selection in MoveTopologyModal
import { computed } from 'vue'

interface FolderTreeNode {
  id: string
  name: string
  children: FolderTreeNode[]
}

interface Props {
  node: FolderTreeNode
  depth?: number
  selectedId: string | null
  expandedIds: Set<string>
}

const props = withDefaults(defineProps<Props>(), {
  depth: 0,
})

const emit = defineEmits<{
  select: [id: string]
  toggle: [id: string]
}>()

const hasChildren = computed(() => props.node.children.length > 0)
const isExpanded = computed(() => props.expandedIds.has(props.node.id))
const isSelected = computed(() => props.selectedId === props.node.id)
</script>

<template>
  <div>
    <div
      class="flex items-center gap-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
      :class="{ 'bg-primary-50 dark:bg-primary-900/20': isSelected }"
      :style="{ paddingLeft: `${depth * 20 + 12}px` }"
    >
      <!-- Expand/collapse arrow -->
      <button
        v-if="hasChildren"
        type="button"
        @click.stop="emit('toggle', node.id)"
        class="shrink-0 p-0.5 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
      >
        <svg
          :class="[
            'w-3 h-3 transition-transform duration-150',
            isExpanded ? 'rotate-90' : ''
          ]"
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
            d="m9 5 5 7-5 7"
          />
        </svg>
      </button>
      <!-- Spacer when no children (align with folders that have arrow) -->
      <span v-else class="shrink-0 w-4"></span>

      <!-- Radio + folder label -->
      <label class="flex-1 flex items-center gap-2 py-2 pr-3 cursor-pointer">
        <input
          type="radio"
          name="move-folder"
          :value="node.id"
          :checked="isSelected"
          @change="emit('select', node.id)"
          class="h-4 w-4 border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
        />
        <svg class="w-4 h-4 text-gray-400 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path d="M3 6a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6Z"/>
        </svg>
        <span class="text-sm text-gray-900 dark:text-white">{{ node.name }}</span>
      </label>
    </div>

    <!-- Children (recursive) -->
    <div v-if="hasChildren && isExpanded">
      <MoveTreeNode
        v-for="child in node.children"
        :key="child.id"
        :node="child"
        :depth="depth + 1"
        :selected-id="selectedId"
        :expanded-ids="expandedIds"
        @select="emit('select', $event)"
        @toggle="emit('toggle', $event)"
      />
    </div>
  </div>
</template>
