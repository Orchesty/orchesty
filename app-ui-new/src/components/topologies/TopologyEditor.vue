<script setup lang="ts">
import { ref, computed } from 'vue'
import { ReteEditorKit } from 'rete-editor'
import type { EditorCore, LabelCustomizationMap } from 'rete-editor'
import CronSettingsModal from '@/components/scheduled-tasks/CronSettingsModal.vue'
import RunProcessModal from '@/components/topologies/RunProcessModal.vue'
import { useCronNodeActions } from '@/composables/useCronNodeActions'
import { useDateFormat } from '@/composables/useDateFormat'
import { fetchTopologySchema, saveTopologySchema } from '@/services/topologiesService'
import { fetchRawTopologyMetrics } from '@/services/topologyMetricsService'
import api from '@/services/api'
import { topologyEditorService } from '@/services/topologyEditorService'
import type { ScheduledTask } from '@/types/scheduled-tasks'
import type { CronNode } from '@/types/topologies-page'

const props = defineProps<{
  topologyId: string
}>()

const emit = defineEmits<{
  'process-run': []
}>()

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

interface BackendNode {
  _id: string
  name: string
  type: string
  schema_id: string
  enabled: boolean
  cron_time: string | null
  cron_params: string | null
}

const editorCore = ref<EditorCore>()
const cronSettingsModalOpen = ref(false)
const runProcessModalOpen = ref(false)
const selectedNode = ref<EditorNode | null>(null)

const nodesData = ref<Record<string, CronNode>>({})
const schemaToBackendId = ref<Record<string, string>>({})

interface NodeMetricsData {
  processTime?: number
  requestTime?: number
}
const nodeMetrics = ref<Record<string, NodeMetricsData>>({})

const resolveBackendId = (editorNodeId: string): string => {
  return schemaToBackendId.value[editorNodeId] || editorNodeId
}

const formatMs = (ms: number): string => {
  if (ms < 1000) return `${Math.round(ms)}ms`
  if (ms < 60000) return `${(ms / 1000).toFixed(1)}s`
  const minutes = Math.floor(ms / 60000)
  const seconds = Math.round((ms % 60000) / 1000)
  return `${minutes}m ${seconds}s`
}

const loadNodeMetrics = async () => {
  try {
    const raw = await fetchRawTopologyMetrics(props.topologyId)

    const backendToEditorId = new Map<string, string>()
    for (const [editorId, backendId] of Object.entries(schemaToBackendId.value)) {
      backendToEditorId.set(backendId, editorId)
    }

    const metrics: Record<string, NodeMetricsData> = {}

    for (const [backendNodeId, duration] of Object.entries(raw.processTimeByNodeId)) {
      const editorId = backendToEditorId.get(backendNodeId)
      if (editorId) {
        if (!metrics[editorId]) metrics[editorId] = {}
        metrics[editorId].processTime = duration
      }
    }

    for (const [backendNodeId, duration] of Object.entries(raw.requestTimeByNodeId)) {
      const editorId = backendToEditorId.get(backendNodeId)
      if (editorId) {
        if (!metrics[editorId]) metrics[editorId] = {}
        metrics[editorId].requestTime = duration
      }
    }

    nodeMetrics.value = metrics
    await editorCore.value?.updateNodeOverlays()
  } catch (error) {
    console.error('Failed to load node metrics:', error)
  }
}

const runAction = {
  id: 'run',
  icon: '<path d="M8 5v14l11-7z"/>',
  label: 'Run',
  tooltip: 'Run Now',
  onClick: (node: EditorNode) => {
    selectedNode.value = node
    runProcessModalOpen.value = true
  }
}

const getOverlayTopRight = (node: EditorNode) => {
  const m = nodeMetrics.value[node.id]
  if (!m?.processTime) return null
  return {
    content: `<span style="font-size:.75rem;font-weight:600;color:#f9fafb;background:#374151;padding:2px 6px;border-radius:9999px;white-space:nowrap;">${formatMs(m.processTime)}</span>`,
  }
}


const overlayMethods = {
  getTopRightSlot: getOverlayTopRight,
}

const createToggleAction = (node: EditorNode) => {
  const nodeData = nodesData.value[node.id]
  if (!nodeData) return null

  const isEnabled = nodeData.enabled ?? true

  return {
    id: 'toggle',
    icon: isEnabled
      ? '<path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm3.707 8.707-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 1 1 1.414-1.414L11 12.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>'
      : '<path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm3.707 6.707a1 1 0 0 1-1.414 1.414L12 7.414 9.707 9.707a1 1 0 1 1-1.414-1.414l3-3a1 1 0 0 1 1.414 0l3 3Z"/>',
    label: isEnabled ? 'Disable' : 'Enable',
    tooltip: isEnabled ? 'Disable' : 'Enable',
    onClick: async (node: EditorNode) => {
      try {
        const backendId = resolveBackendId(node.id)
        const newState = await toggleNodeState(backendId, isEnabled)

        const nodeData = nodesData.value[node.id]
        if (nodeData) {
          nodeData.enabled = newState
        }

        editorCore.value?.toggleNodeDisabled(node.id)
        editorCore.value?.refreshNodeLabel(node.id)
      } catch (error) {
        console.error('Failed to toggle node state:', error)
      }
    }
  }
}

const eventNodeActions = {
  getActions: (node: EditorNode) => {
    const actions = []
    const toggle = createToggleAction(node)
    if (toggle) actions.push(toggle)
    actions.push(runAction)
    return actions
  },
  ...overlayMethods,
}

const labelCustomization: LabelCustomizationMap = {
  Event: eventNodeActions,
  Webhook: eventNodeActions,
  Connector: { ...overlayMethods },
  'Custom Action': { ...overlayMethods },
  Batch: { ...overlayMethods },
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
      const actions = []
      const toggle = createToggleAction(node)
      if (toggle) actions.push(toggle)
      actions.push(runAction)
      actions.push({
        id: 'settings',
        icon: '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
        label: 'Settings',
        tooltip: 'Settings',
        onClick: (node: EditorNode) => {
          selectedNode.value = node
          cronSettingsModalOpen.value = true
        }
      })
      return actions
    },
    ...overlayMethods,
  }
}

const editorConfig = createConfig({
  mode: 'readonly',
  canvasHeight: 'calc(100vh - 280px)',
  labelCustomization
})

const fetchBackendNodes = async (): Promise<BackendNode[]> => {
  try {
    const response = await api.get(`/api/topologies/${props.topologyId}/nodes`)
    return (response.data.items || []) as BackendNode[]
  } catch (error) {
    console.error('Failed to fetch backend nodes:', error)
    return []
  }
}

const initNodesData = (editor: EditorCore, backendNodes: BackendNode[]) => {
  nodesData.value = {}
  schemaToBackendId.value = {}

  const backendBySchemaId = new Map<string, BackendNode>()
  const backendByName = new Map<string, BackendNode>()
  for (const bn of backendNodes) {
    if (bn.schema_id) {
      backendBySchemaId.set(bn.schema_id, bn)
    }
    if (bn.name) {
      backendByName.set(bn.name, bn)
    }
  }

  const editorNodes = editor.getNodes()
  for (const node of editorNodes) {
    const editorNode = node as unknown as EditorNode
    const backend = backendBySchemaId.get(editorNode.id)
      || (editorNode.name ? backendByName.get(editorNode.name) : undefined)

    if (backend) {
      schemaToBackendId.value[editorNode.id] = backend._id
    }

    const label = (editorNode.label || '').toLowerCase()
    if (label === 'cron') {
      nodesData.value[editorNode.id] = {
        id: editorNode.id,
        label: editorNode.label,
        name: editorNode.name,
        crontab: backend?.cron_time || '',
        enabled: backend?.enabled ?? true,
        nextRun: ''
      }
    } else if (label === 'event' || label === 'webhook') {
      nodesData.value[editorNode.id] = {
        id: editorNode.id,
        label: editorNode.label,
        name: editorNode.name,
        crontab: '',
        enabled: backend?.enabled ?? true,
        nextRun: ''
      }
    }
  }
}

const onEditorReady = async (editor: EditorCore) => {
  editorCore.value = editor

  try {
    const [schema, backendNodes, actions] = await Promise.all([
      fetchTopologySchema(props.topologyId),
      fetchBackendNodes(),
      topologyEditorService.getAllActions()
    ])
    await editor.importGraph(schema)
    await editor.setActions(actions)
    initNodesData(editor, backendNodes)
    editor.zoomToFit()
    loadNodeMetrics()
  } catch (error) {
    console.error('Failed to load topology data:', error)
  }
}

const selectedNodeAsTask = computed<ScheduledTask | null>(() => {
  if (!selectedNode.value) return null

  const nodeData = nodesData.value[selectedNode.value.id]
  if (!nodeData) return null

  return {
    id: resolveBackendId(selectedNode.value.id),
    name: selectedNode.value.name || selectedNode.value.label,
    topology: '',
    topologyId: props.topologyId,
    crontab: nodeData.crontab || '',
    status: nodeData.enabled ? 'enabled' : 'disabled'
  }
})

const handleCrontabSave = async (backendNodeId: string, crontab: string) => {
  if (!selectedNode.value) return

  try {
    const nextRun = await updateCrontab(backendNodeId, crontab)

    const editorNodeId = selectedNode.value.id
    const nodeData = nodesData.value[editorNodeId]
    if (nodeData) {
      nodeData.crontab = crontab
      nodeData.nextRun = nextRun
    }

    editorCore.value?.refreshNodeLabel(editorNodeId)
    cronSettingsModalOpen.value = false
  } catch (error) {
    console.error('Failed to save crontab:', error)
  }
}

const reloadSchema = async () => {
  if (!editorCore.value) return
  try {
    const [schema, backendNodes, actions] = await Promise.all([
      fetchTopologySchema(props.topologyId),
      fetchBackendNodes(),
      topologyEditorService.getAllActions()
    ])
    await editorCore.value.importGraph(schema)
    await editorCore.value.setActions(actions)
    initNodesData(editorCore.value, backendNodes)
    editorCore.value.zoomToFit()
  } catch (error) {
    console.error('Failed to reload topology schema:', error)
  }
}

defineExpose({ reloadSchema, loadNodeMetrics })

const handleRunProcess = async (jsonData: string) => {
  if (!selectedNode.value) return

  try {
    const backendId = resolveBackendId(selectedNode.value.id)
    await runProcess(
      props.topologyId,
      backendId,
      selectedNode.value.name || selectedNode.value.label,
      jsonData
    )
    emit('process-run')
  } catch (error) {
    console.error('Failed to run process:', error)
  }
}

const handlePositionChanged = async () => {
  if (!editorCore.value) return
  try {
    const graph = editorCore.value.exportGraph()
    await saveTopologySchema(props.topologyId, graph)
  } catch (error) {
    console.error('Failed to save topology layout:', error)
  }
}
</script>

<template>
  <div>
    <Editor :config="editorConfig" @ready="onEditorReady" @node-position-changed="handlePositionChanged" />

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

