<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import MoveTreeNode from '@/components/topologies/MoveTreeNode.vue'
import { fetchCategories, updateTopology } from '@/services/topologiesService'
import { useToast } from '@/composables/useToast'
import type { FolderItem } from '@/types/topologies-page'

// Tree node for the folder tree inside the modal
interface FolderTreeNode {
  id: string
  name: string
  children: FolderTreeNode[]
}

interface Props {
  modelValue: boolean
  topologyId: string
  topologyName?: string
  currentCategoryId?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  topologyName: '',
  currentCategoryId: null,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  moved: []
}>()

const { showToast } = useToast()

const flatFolders = ref<FolderItem[]>([])
const selectedFolderId = ref<string | null>(null)
const expandedIds = ref<Set<string>>(new Set())
const loading = ref(false)
const saving = ref(false)

// Sort folders alphabetically
const sortFolderNodes = (nodes: FolderTreeNode[]) => {
  nodes.sort((a, b) => a.name.localeCompare(b.name))
}

// Build tree from flat folder list
const folderTree = computed<FolderTreeNode[]>(() => {
  const map = new Map<string, FolderTreeNode>()
  const roots: FolderTreeNode[] = []

  // Create nodes
  for (const folder of flatFolders.value) {
    map.set(folder.id, { id: folder.id, name: folder.name, children: [] })
  }

  // Build parent-child relationships
  for (const folder of flatFolders.value) {
    const node = map.get(folder.id)!
    if (folder.parentFolderId && map.has(folder.parentFolderId)) {
      map.get(folder.parentFolderId)!.children.push(node)
    } else {
      roots.push(node)
    }
  }

  // Sort all levels alphabetically
  sortFolderNodes(roots)
  for (const [, node] of map) {
    if (node.children.length > 0) {
      sortFolderNodes(node.children)
    }
  }

  return roots
})

// Toggle expand/collapse
const toggleExpanded = (id: string) => {
  const newSet = new Set(expandedIds.value)
  if (newSet.has(id)) {
    newSet.delete(id)
  } else {
    newSet.add(id)
  }
  expandedIds.value = newSet
}

// Expand all ancestors of a folder so it's visible
const expandAncestors = (targetId: string) => {
  const parentMap = new Map<string, string>()
  for (const folder of flatFolders.value) {
    if (folder.parentFolderId) {
      parentMap.set(folder.id, folder.parentFolderId)
    }
  }

  const newSet = new Set(expandedIds.value)
  let current = parentMap.get(targetId)
  while (current) {
    newSet.add(current)
    current = parentMap.get(current)
  }
  expandedIds.value = newSet
}

const loadFolders = async () => {
  loading.value = true
  try {
    flatFolders.value = await fetchCategories()
  } catch (error) {
    console.error('Failed to load folders:', error)
    showToast('Failed to load folders', 'error')
  } finally {
    loading.value = false
  }
}

// Reset and load when modal opens
watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue) {
      selectedFolderId.value = props.currentCategoryId ?? null
      expandedIds.value = new Set()
      loadFolders().then(() => {
        // Expand ancestors of currently selected folder so it's visible
        if (props.currentCategoryId) {
          expandAncestors(props.currentCategoryId)
        }
      })
    }
  },
)

const handleClose = () => {
  emit('update:modelValue', false)
}

const handleMove = async () => {
  if (selectedFolderId.value === (props.currentCategoryId ?? null)) {
    handleClose()
    return
  }

  saving.value = true
  try {
    await updateTopology(props.topologyId, { category: selectedFolderId.value })
    showToast('Topology moved successfully', 'success')
    emit('moved')
    handleClose()
  } catch (error) {
    console.error('Failed to move topology:', error)
    showToast('Failed to move topology', 'error')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="move-topology-modal"
    :title="`Move ${topologyName || 'Topology'}`"
    size="md"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <div class="space-y-2">
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
        Select a destination folder:
      </p>

      <!-- Loading -->
      <div v-if="loading" class="flex items-center justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
      </div>

      <!-- Folder tree -->
      <div v-else class="max-h-64 overflow-y-auto">
        <!-- Root option (no folder) -->
        <label
          class="flex items-center gap-3 px-3 py-2 rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700"
          :class="{ 'bg-primary-50 dark:bg-primary-900/20': selectedFolderId === null }"
        >
          <input
            type="radio"
            name="move-folder"
            :checked="selectedFolderId === null"
            @change="selectedFolderId = null"
            class="h-4 w-4 border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
          />
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path d="M3 6a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6Z"/>
            </svg>
            <span class="text-sm font-medium text-gray-900 dark:text-white">Root (no folder)</span>
          </div>
        </label>

        <!-- Recursive folder tree -->
        <template v-for="node in folderTree" :key="node.id">
          <MoveTreeNode
            :node="node"
            :depth="0"
            :selected-id="selectedFolderId"
            :expanded-ids="expandedIds"
            @select="selectedFolderId = $event"
            @toggle="toggleExpanded($event)"
          />
        </template>

        <!-- Empty state -->
        <p v-if="!loading && flatFolders.length === 0" class="py-4 text-center text-sm text-gray-500 dark:text-gray-400">
          No folders available. Create a folder first.
        </p>
      </div>
    </div>

    <template #footer-actions>
      <Button variant="outline" @click="handleClose">
        Cancel
      </Button>
      <Button
        variant="primary"
        :disabled="saving"
        @click="handleMove"
      >
        {{ saving ? 'Moving...' : 'Move' }}
      </Button>
    </template>
  </Modal>
</template>
