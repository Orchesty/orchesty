<script setup lang="ts">
import { ref, computed } from 'vue'
import { ReteEditorKit } from 'rete-editor'
import type { EditorCore, LabelCustomizationMap } from 'rete-editor'
import topologyData from '@/assets/mock-data/topologies/data-stream.json'
import CronSettingsModal from '@/components/scheduled-tasks/CronSettingsModal.vue'
import RunProcessModal from '@/components/topologies/RunProcessModal.vue'
import { useCronNodeActions } from '@/composables/useCronNodeActions'
import { useDateFormat } from '@/composables/useDateFormat'
import type { ScheduledTask } from '@/types/scheduled-tasks'
import type { CronNode } from '@/types/topologies-page'

const { Editor, createConfig } = ReteEditorKit
const { toggleNodeState, runProcess, updateCrontab } = useCronNodeActions()
const { formatDateTime } = useDateFormat()

interface EditorNode {
  id: string
  label: string
  name: string
  type?: string
  [key: string]: unknown
}

const editorCore = ref<EditorCore>()
const cronSettingsModalOpen = ref(false)
const runProcessModalOpen = ref(false)
const selectedNode = ref<EditorNode | null>(null)

// Create a reactive ref for all nodes to track their state
const nodesData = ref<Record<string, CronNode>>({})

// Label customization for Cron nodes
const labelCustomization: LabelCustomizationMap = {
  Cron: {
    getFields: (node: EditorNode) => {
      const nodeData = nodesData.value[node.id]
      if (!nodeData) return []
      
      return [
        { label: 'Crontab', value: nodeData.crontab || 'Not set' },
        { 
          label: 'Next run', 
          value: nodeData.nextRun 
            ? formatDateTime(nodeData.nextRun)
            : 'N/A' 
        }
      ]
    },
    getActions: (node: EditorNode) => {
      const nodeData = nodesData.value[node.id]
      if (!nodeData) return []
      
      const isEnabled = nodeData.enabled ?? true
      
      return [
        {
          id: 'toggle',
          icon: isEnabled 
            ? '<path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm3.707 8.707-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 1 1 1.414-1.414L11 12.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>' 
            : '<path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm3.707 6.707a1 1 0 0 1-1.414 1.414L12 7.414 9.707 9.707a1 1 0 1 1-1.414-1.414l3-3a1 1 0 0 1 1.414 0l3 3Z"/>',
          label: isEnabled ? 'Disable' : 'Enable',
          tooltip: isEnabled ? 'Disable' : 'Enable',
          onClick: async (node: EditorNode) => {
            try {
              const newState = await toggleNodeState(node.id, isEnabled)
              
              // Update local state
              const nodeData = nodesData.value[node.id]
              if (nodeData) {
                nodeData.enabled = newState
              }
              
              // Toggle disabled state in editor (visual indication)
              editorCore.value?.toggleNodeDisabled(node.id)
              
              // Refresh label to show updated state
              editorCore.value?.refreshNodeLabel(node.id)
            } catch (error) {
              console.error('Failed to toggle node state:', error)
            }
          }
        },
        {
          id: 'run',
          icon: '<path d="M8 5v14l11-7z"/>',
          label: 'Run',
          tooltip: 'Run Now',
          onClick: (node: EditorNode) => {
            selectedNode.value = node
            runProcessModalOpen.value = true
          }
        },
        {
          id: 'settings',
          icon: '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
          label: 'Settings',
          tooltip: 'Settings',
          onClick: (node: EditorNode) => {
            selectedNode.value = node
            cronSettingsModalOpen.value = true
          }
        }
      ]
    }
  }
}

const editorConfig = createConfig({
  mode: 'readonly',
  canvasHeight: 'calc(100vh - 280px)',
  labelCustomization
})

const onEditorReady = async (editor: EditorCore) => {
  editorCore.value = editor
  
  try {
    // Import workflow from mock data
    await editor.importGraph(topologyData)
    
    // Initialize nodes data with imported data
    const nodes = editor.getNodes()
    nodes.forEach((node) => {
      const editorNode = node as unknown as EditorNode
      if (editorNode.label === 'Cron') {
        const cronData = editorNode as EditorNode & Partial<CronNode>
        nodesData.value[editorNode.id] = {
          id: editorNode.id,
          label: editorNode.label,
          name: editorNode.name,
          crontab: (cronData.crontab as string) || '*/15 * * * *',
          enabled: cronData.enabled !== undefined ? (cronData.enabled as boolean) : true,
          nextRun: (cronData.nextRun as string) || new Date(Date.now() + 15 * 60 * 1000).toISOString()
        }
      }
    })
    
    // Fit all nodes to view
    editor.zoomToFit()
  } catch (error) {
    console.error('Failed to load topology data:', error)
  }
}

// Convert selected node to ScheduledTask format for CronSettingsModal
const selectedNodeAsTask = computed<ScheduledTask | null>(() => {
  if (!selectedNode.value) return null
  
  const nodeData = nodesData.value[selectedNode.value.id]
  if (!nodeData) return null
  
  return {
    id: selectedNode.value.id,
    name: selectedNode.value.name || selectedNode.value.label,
    topology: 'Current Topology',
    topologyId: 'current-topology-id',
    crontab: nodeData.crontab || '*/15 * * * *',
    status: nodeData.enabled ? 'enabled' : 'disabled'
  }
})

const handleCrontabSave = async (taskId: string, crontab: string) => {
  try {
    const nextRun = await updateCrontab(taskId, crontab)
    
    // Update local state
    const nodeData = nodesData.value[taskId]
    if (nodeData) {
      nodeData.crontab = crontab
      nodeData.nextRun = nextRun
    }
    
    // Refresh label to show updated crontab and next run
    editorCore.value?.refreshNodeLabel(taskId)
    
    cronSettingsModalOpen.value = false
  } catch (error) {
    console.error('Failed to save crontab:', error)
  }
}

const handleRunProcess = async (jsonData: string) => {
  if (!selectedNode.value) return
  
  try {
    await runProcess(
      selectedNode.value.id,
      selectedNode.value.name || selectedNode.value.label,
      jsonData
    )
  } catch (error) {
    console.error('Failed to run process:', error)
  }
}
</script>

<template>
  <div>
    <Editor :config="editorConfig" @ready="onEditorReady" />
    
    <CronSettingsModal
      v-model="cronSettingsModalOpen"
      :task="selectedNodeAsTask"
      @save="handleCrontabSave"
    />
    
    <RunProcessModal
      v-model="runProcessModalOpen"
      :node-name="selectedNode?.name || selectedNode?.label"
      :node-id="selectedNode?.id"
      @run="handleRunProcess"
    />
  </div>
</template>

<style scoped>
/* Hide property control in readonly mode */
:deep(.property-control) {
  display: none;
}
</style>

