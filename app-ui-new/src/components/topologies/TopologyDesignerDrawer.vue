<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { Drawer } from 'flowbite'
import type { DrawerInterface } from 'flowbite'
import Button from '@/components/ui/Button.vue'
import { ReteEditorKit } from 'rete-editor'
import type { EditorCore } from 'rete-editor'
import topologyData from '@/assets/mock-data/topologies/data-stream.json'
import { useToast } from '@/composables/useToast'
// Note: Available actions for editor are defined in @/assets/mock-data/actions.ts
// In production, actions would be loaded from backend via topologyEditorService
import { topologyEditorService } from '@/services/topologyEditorService'

interface Props {
  modelValue: boolean
  topologyName: string
  topologyVersion: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  save: []
}>()

const { Editor, createConfig } = ReteEditorKit
const { showToast } = useToast()

let drawerInstance: DrawerInterface | null = null
const editorCore = ref<EditorCore>()

// Create configuration for edit mode
const editorConfig = createConfig({
  mode: 'edit',
  canvasHeight: 'calc(100vh - 53px)'
})

const onEditorReady = async (editor: EditorCore) => {
  editorCore.value = editor
  
  try {
    // Import workflow from mock data
    await editor.importGraph(topologyData)
    
    // Fit all nodes to view
    editor.zoomToFit()
    
    // Log available actions (for development)
    const actions = await topologyEditorService.getAllActions()
    console.log(`✅ Editor ready with ${actions.length} available actions`)
    console.log('Available action types:', {
      custom: actions.filter(a => a.type === 'custom').length,
      connector: actions.filter(a => a.type === 'connector').length,
      batch: actions.filter(a => a.type === 'batch').length
    })
  } catch (error) {
    console.error('Failed to load topology data:', error)
    showToast({
      type: 'error',
      message: 'Failed to load topology data',
      duration: 3000
    })
  }
}

const handleSave = async () => {
  if (!editorCore.value) {
    showToast({
      type: 'error',
      message: 'Editor not initialized',
      duration: 3000
    })
    return
  }

  try {
    // Export current workflow
    const workflow = editorCore.value.exportGraph()
    console.log('Saving workflow:', workflow)
    
    // Simulate save to backend
    await new Promise(resolve => setTimeout(resolve, 500))
    
    showToast({
      type: 'success',
      message: 'Topology saved successfully',
      duration: 3000
    })
    
    emit('save')
  } catch (error) {
    console.error('Failed to save topology:', error)
    showToast({
      type: 'error',
      message: 'Failed to save topology',
      duration: 3000
    })
  }
}

const handleClose = () => {
  if (drawerInstance) {
    drawerInstance.hide()
  }
}

onMounted(() => {
  const drawerEl = document.getElementById('topology-designer-drawer')
  if (drawerEl) {
    drawerInstance = new Drawer(drawerEl, {
      placement: 'right',
      backdrop: true,
      bodyScrolling: false,
      edge: false,
      edgeOffset: '',
      backdropClasses: 'bg-gray-900/50 dark:bg-gray-900/80 fixed inset-0 z-[55]',
      onHide: () => {
        emit('update:modelValue', false)
      },
      onShow: () => {
        emit('update:modelValue', true)
      }
    })
  }
})

watch(
  () => props.modelValue,
  (newValue) => {
    if (drawerInstance) {
      if (newValue) {
        drawerInstance.show()
      } else {
        drawerInstance.hide()
      }
    }
  }
)
</script>

<template>
  <div
    id="topology-designer-drawer"
    class="fixed top-0 right-0 z-[60] h-screen overflow-hidden transition-transform translate-x-full bg-gray-50 w-full dark:bg-gray-900 flex flex-col"
    tabindex="-1"
    aria-labelledby="topology-designer-drawer-label"
    role="dialog"
    aria-modal="true"
  >
    <!-- Topbar -->
    <nav class="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-2.5 dark:border-gray-700 dark:bg-gray-800 relative flex-shrink-0">
      <!-- Logo left -->
      <div>
        <img src="/logo.svg" alt="Orchesty" class="h-8 w-8" />
      </div>
      
      <!-- Title centered -->
      <h5
        id="topology-designer-drawer-label"
        class="absolute left-1/2 transform -translate-x-1/2 text-lg font-semibold text-gray-900 dark:text-white"
      >
        {{ topologyName }} <span class="text-sm font-normal text-gray-500 dark:text-gray-400">Version {{ topologyVersion }}</span>
      </h5>
      
      <div class="flex items-center gap-2">
        <Button variant="outline" @click="handleClose">
          Close
        </Button>
        <Button @click="handleSave">
          Save
        </Button>
      </div>
    </nav>
    
    <!-- Editor fills remaining space -->
    <div class="flex-1 overflow-hidden ">
      <Editor :config="editorConfig" @ready="onEditorReady" />
    </div>
  </div>
</template>

