<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { Drawer } from 'flowbite'
import type { DrawerInterface } from 'flowbite'
import Button from '@/components/ui/Button.vue'
import { ReteEditorKit } from 'rete-editor'
import type { EditorCore, EditorConfig } from 'rete-editor'
import { useToast } from '@/composables/useToast'
import { topologyEditorService } from '@/services/topologyEditorService'
import { fetchTopologySchema, saveTopologySchema } from '@/services/topologiesService'

interface Props {
  modelValue: boolean
  topologyId: string
  topologyName: string
  topologyVersion: string | number
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  save: [data: { _id: string }]
}>()

const { Editor, createConfig } = ReteEditorKit
const { showToast } = useToast()

let drawerInstance: DrawerInterface | null = null
const editorCore = ref<EditorCore>()
const editorConfig = ref<EditorConfig | null>(null)

const initEditorConfig = async () => {
  try {
    const actions = await topologyEditorService.getAllActions()
    editorConfig.value = createConfig({
      mode: 'edit',
      canvasHeight: 'calc(100vh - 53px)',
      actions
    })
  } catch (error) {
    console.error('Failed to load actions:', error)
    editorConfig.value = createConfig({
      mode: 'edit',
      canvasHeight: 'calc(100vh - 53px)'
    })
  }
}

const onEditorReady = async (editor: EditorCore) => {
  editorCore.value = editor
  
  try {
    const [schema, actions] = await Promise.all([
      fetchTopologySchema(props.topologyId),
      topologyEditorService.getAllActions(),
    ])
    await editor.importGraph(schema)
    await editor.setActions(actions)
    editor.zoomToFit()
  } catch (error) {
    console.error('Failed to load topology data:', error)
    showToast('Failed to load topology data', 'error', 3000)
  }
}

watch(
  () => props.topologyId,
  async (newId) => {
    if (editorCore.value && newId) {
      try {
        const schema = await fetchTopologySchema(newId)
        await editorCore.value.importGraph(schema)
        editorCore.value.zoomToFit()
      } catch (error) {
        console.error('Failed to reload topology data:', error)
      }
    }
  }
)

const handleSave = async () => {
  if (!editorCore.value) {
    showToast('Editor not initialized', 'error', 3000)
    return
  }

  try {
    const workflow = editorCore.value.exportGraph()
    const result = await saveTopologySchema(props.topologyId, workflow)
    
    showToast('Topology saved successfully', 'success', 3000)
    
    emit('save', result)
  } catch (error) {
    console.error('Failed to save topology:', error)
    showToast('Failed to save topology', 'error', 3000)
  }
}

const handleClose = () => {
  if (drawerInstance) {
    drawerInstance.hide()
  }
}

onMounted(async () => {
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

  await initEditorConfig()
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
    <nav class="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-2.5 dark:border-gray-700 dark:bg-gray-800 relative shrink-0">
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
    <div class="flex-1 overflow-hidden">
      <Editor v-if="editorConfig" :config="editorConfig" @ready="onEditorReady" />
    </div>
  </div>
</template>

