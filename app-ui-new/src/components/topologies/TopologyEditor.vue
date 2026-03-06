<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { ReteEditorKit } from 'rete-editor'
import type { EditorCore, LabelCustomizationMap } from 'rete-editor'
import CronSettingsModal from '@/components/scheduled-tasks/CronSettingsModal.vue'
import RunProcessModal from '@/components/topologies/RunProcessModal.vue'
import { useCronNodeActions } from '@/composables/useCronNodeActions'
import { useDateFormat } from '@/composables/useDateFormat'
import { useProcessPolling } from '@/composables/useProcessPolling'
import { fetchTopologySchema, saveTopologySchema } from '@/services/topologiesService'
import { fetchRawTopologyMetrics } from '@/services/topologyMetricsService'
import { fetchTrashItems } from '@/services/trashService'
import api from '@/services/api'
import { topologyEditorService } from '@/services/topologyEditorService'
import type { ScheduledTask } from '@/types/scheduled-tasks'
import type { CronNode } from '@/types/topologies-page'
import type { TrashItem } from '@/types/trash'

interface Props {
  topologyId: string
  topologyEnabled?: boolean
  refreshKey?: number
}

const props = withDefaults(defineProps<Props>(), {
  topologyEnabled: true,
  refreshKey: 0,
})

const emit = defineEmits<{
  'process-run': []
  'open-failed-message': [item: TrashItem]
}>()

const { Editor, createConfig } = ReteEditorKit
const { toggleNodeState, runProcess, updateCrontab } = useCronNodeActions()
const { formatDateTime } = useDateFormat()
const polling = useProcessPolling(props.topologyId)

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

const nodeStatuses = ref<Record<string, boolean>>({})

const errorIconSvg = '<svg style="width:40px;height:40px;" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" fill="#dc2626" stroke="#dc2626"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'

const handleErrorIconClick = async (editorNodeId: string) => {
  const backendNodeId = resolveBackendId(editorNodeId)
  const correlationId = polling.latestProcess.value?.id
  if (!correlationId) return

  try {
    const result = await fetchTrashItems({
      correlationId,
      node: backendNodeId,
      topology: props.topologyId,
      perPage: 1,
      sortBy: 'timestamp',
      sortOrder: 'desc',
    })
    const item = result.data[0]
    if (item) {
      emit('open-failed-message', item)
    }
  } catch (err) {
    console.error('Failed to fetch failed message for node:', err)
  }
}

const getOverlayTopLeft = (node: EditorNode) => {
  if (!nodeStatuses.value[node.id]) return null
  return {
    content: errorIconSvg,
    onClick: () => handleErrorIconClick(node.id),
  }
}

const overlayMethods = {
  getTopRightSlot: getOverlayTopRight,
  getTopLeftSlot: getOverlayTopLeft,
}

const createToggleAction = (node: EditorNode) => {
  const nodeData = nodesData.value[node.id]
  if (!nodeData) return null

  return {
    id: 'toggle',
    icon: '<path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm3.707 8.707-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 1 1 1.414-1.414L11 12.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>',
    label: 'Toggle',
    tooltip: 'Toggle',
    onClick: async (node: EditorNode) => {
      try {
        const currentEnabled = nodesData.value[node.id]?.enabled ?? true
        const backendId = resolveBackendId(node.id)
        const newState = await toggleNodeState(backendId, currentEnabled)

        const nd = nodesData.value[node.id]
        if (nd) {
          nd.enabled = newState
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
          value: nodeData.nextRun && props.topologyEnabled && nodeData.enabled
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

    const disabledNodeIds = Object.entries(nodesData.value)
      .filter(([, data]) => !data.enabled)
      .map(([id]) => id)
    if (disabledNodeIds.length > 0) {
      await editor.setDisabledNodes(disabledNodeIds)
    }

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
      nodeData.nextRun = props.topologyEnabled && nodeData.enabled ? nextRun : ''
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

const processStartNodeName = ref<string | null>(null)
const processStartedAt = ref<string | null>(null)

const processStatus = computed(() => {
  const process = polling.latestProcess.value
  if (!process) return polling.isPolling.value ? 'running' : null
  if (process.status === 'completed') return 'success'
  if (process.status === 'failed') return 'failed'
  return 'running'
})

const statusBadgeClass = computed(() => {
  const base = 'text-xs font-medium px-2.5 py-0.5 rounded-full'
  switch (processStatus.value) {
    case 'running': return `${base} bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300`
    case 'success': return `${base} bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300`
    case 'failed': return `${base} bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300`
    default: return base
  }
})

const statusLabel = computed(() => {
  switch (processStatus.value) {
    case 'running': return 'Running'
    case 'success': return 'Success'
    case 'failed': return 'Failed'
    default: return ''
  }
})

const displayStartTime = computed(() =>
  polling.latestProcess.value?.startTime || processStartedAt.value
)

const processEndTime = computed(() => {
  const p = polling.latestProcess.value
  if (!p?.startTime || !p?.duration) return null
  const start = new Date(p.startTime).getTime()
  return new Date(start + p.duration).toISOString()
})

const processDuration = computed(() => {
  const p = polling.latestProcess.value
  if (!p?.duration) return null
  return formatMs(p.duration)
})

const showProcessPanel = computed(() =>
  processStartNodeName.value && (polling.isPolling.value || polling.processCompleted.value)
)

const dismissProcessPanel = () => {
  processStartNodeName.value = null
  processStartedAt.value = null
  polling.latestProcess.value = null
  polling.processCompleted.value = false
  nodeStatuses.value = {}
}

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
    nodeStatuses.value = {}
    processStartNodeName.value = selectedNode.value.name || selectedNode.value.label
    processStartedAt.value = new Date().toISOString()
    polling.startPolling()
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

const buildBackendToEditorMap = (): Map<string, string> => {
  const map = new Map<string, string>()
  for (const [editorId, backendId] of Object.entries(schemaToBackendId.value)) {
    map.set(backendId, editorId)
  }
  return map
}

const applyPolledMetrics = () => {
  const raw = polling.rawMetrics.value
  if (!raw) return

  const backendToEditor = buildBackendToEditorMap()
  const metrics: Record<string, NodeMetricsData> = {}

  for (const [backendNodeId, duration] of Object.entries(raw.processTimeByNodeId)) {
    const editorId = backendToEditor.get(backendNodeId)
    if (editorId) {
      if (!metrics[editorId]) metrics[editorId] = {}
      metrics[editorId].processTime = duration
    }
  }

  for (const [backendNodeId, duration] of Object.entries(raw.requestTimeByNodeId)) {
    const editorId = backendToEditor.get(backendNodeId)
    if (editorId) {
      if (!metrics[editorId]) metrics[editorId] = {}
      metrics[editorId].requestTime = duration
    }
  }

  nodeMetrics.value = metrics
}

const applyNodeStatuses = () => {
  const backendToEditor = buildBackendToEditorMap()
  const statuses: Record<string, boolean> = {}

  for (const backendNodeId of polling.failedNodeIds.value) {
    const editorId = backendToEditor.get(backendNodeId)
    if (editorId) {
      statuses[editorId] = true
    }
  }

  nodeStatuses.value = statuses
}

watch(() => polling.rawMetrics.value, async () => {
  applyPolledMetrics()
  await editorCore.value?.updateNodeOverlays()
})

watch(() => polling.processCompleted.value, async (completed) => {
  if (completed) {
    applyPolledMetrics()
    applyNodeStatuses()
    await editorCore.value?.updateNodeOverlays()
  }
})

watch(() => props.refreshKey, () => {
  if (polling.isPolling.value) return
  nodeStatuses.value = {}
  const startNode = Object.values(nodesData.value).find(n =>
    ['event', 'webhook', 'cron'].includes(n.label.toLowerCase())
  )
  processStartNodeName.value = startNode?.name || 'topology'
  processStartedAt.value = new Date().toISOString()
  polling.startPolling()
})
</script>

<template>
  <div class="relative">
    <Editor :config="editorConfig" @ready="onEditorReady" @node-position-changed="handlePositionChanged" />

    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 -translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-1"
    >
      <div
        v-if="showProcessPanel"
        class="absolute top-3 left-3 z-50 rounded-lg border shadow-lg px-4 py-3 text-sm min-w-[280px]
               bg-white border-gray-200 dark:bg-gray-800 dark:border-gray-700"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="space-y-1.5">
            <div class="flex items-center gap-2">
              <span class="font-semibold text-gray-900 dark:text-white">
                Process from event {{ processStartNodeName }}
              </span>
              <span :class="statusBadgeClass">{{ statusLabel }}</span>
            </div>
            <div v-if="displayStartTime" class="text-gray-500 dark:text-gray-400">
              Start: {{ formatDateTime(displayStartTime) }}
            </div>
            <div v-if="processEndTime" class="text-gray-500 dark:text-gray-400">
              End: {{ formatDateTime(processEndTime) }}
            </div>
            <div v-if="processDuration" class="text-gray-500 dark:text-gray-400">
              Duration: {{ processDuration }}
            </div>
          </div>
          <button
            @click="dismissProcessPanel"
            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-0.5 -mt-0.5"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </Transition>

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

