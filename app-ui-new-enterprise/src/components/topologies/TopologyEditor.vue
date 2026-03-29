<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { ReteEditorKit } from 'rete-editor'
import type { EditorCore, LabelCustomizationMap } from 'rete-editor'
import CronSettingsModal from '@/components/scheduled-tasks/CronSettingsModal.vue'
import RunProcessModal from '@/components/topologies/RunProcessModal.vue'
import BreakpointModal from '@/components/topologies/BreakpointModal.vue'
import FailedMessageModal from '@/components/topologies/FailedMessageModal.vue'
import CopyValue from '@/components/ui/CopyValue.vue'
import { useCronNodeActions } from '@/composables/useCronNodeActions'
import { getNextCronRun } from '@/utils/cronParser'
import { useToast } from '@/composables/useToast'
import { useDateFormat } from '@/composables/useDateFormat'
import { useProcessPolling } from '@/composables/useProcessPolling'
import { fetchTopologySchema, saveTopologySchema } from '@/services/topologiesService'
import {
  approveAllBreakpoints,
  rejectAllBreakpoints,
} from '@/services/breakpointService'
import { fetchLatestProcess } from '@/services/processesService'
import api from '@/services/api'
import { topologyEditorService } from '@/services/topologyEditorService'
import type { ScheduledTask } from '@/types/scheduled-tasks'
import type { CronNode } from '@/types/topologies-page'

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
}>()

const { Editor, createConfig } = ReteEditorKit
const { toggleNodeState, runProcess, updateCrontab } = useCronNodeActions()
const { showToast } = useToast()
const { formatDateTime, formatDurationMs } = useDateFormat()
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
const breakpointModalOpen = ref(false)
const failedMessageModalOpen = ref(false)
const failedMessageNodeId = ref('')
const failedMessageCorrelationId = ref('')
const failedMessageNodeName = ref('')
const selectedNode = ref<EditorNode | null>(null)
const breakpointCounts = ref<Record<string, number | string>>({})
const hasBreakpoints = ref(false)
let cachedSchema: any = null

const nodesData = ref<Record<string, CronNode>>({})
const schemaToBackendId = ref<Record<string, string>>({})

interface NodeMetricsData {
  processTime?: number
  requestTime?: number
  trashCount?: number
  limiterCount?: number
  repeaterCount?: number
}
const nodeMetrics = ref<Record<string, NodeMetricsData>>({})

const resolveBackendId = (editorNodeId: string): string => {
  return schemaToBackendId.value[editorNodeId] || editorNodeId
}

const getStartingPointUrl = (editorNodeId: string): string => {
  const backendId = resolveBackendId(editorNodeId)
  const baseUrl = import.meta.env.VITE_BACKEND_URL || 'http://127.0.0.66:8085'
  return `${baseUrl}/topologies/${props.topologyId}/nodes/${backendId}/run`
}


const EXCLUDED_PROCESS_TIME_LABELS = new Set(['event', 'webhook', 'cron', 'breakpoint'])

const isProcessTimeRelevant = (editorId: string): boolean => {
  const label = nodesData.value[editorId]?.label?.toLowerCase()
  return !label || !EXCLUDED_PROCESS_TIME_LABELS.has(label)
}

const buildMetricsLabelHtml = (node: EditorNode, isCustomAction: boolean): string | null => {
  const m = nodeMetrics.value[node.id]
  if (!m) return null

  const boxStyle = 'text-align:center;padding:3px 10px;'
  const labelStyle = 'font-size:.8rem;opacity:.6;line-height:1.6;'
  const valueStyle = 'font-size:1rem;line-height:1.8;'

  interface MetricDef { key: keyof NodeMetricsData; label: string; isTime: boolean }

  const allMetrics: MetricDef[] = isCustomAction
    ? [
        { key: 'processTime', label: 'Process', isTime: true },
        { key: 'trashCount', label: 'Trash', isTime: false },
      ]
    : [
        { key: 'processTime', label: 'Process', isTime: true },
        { key: 'requestTime', label: 'Request', isTime: true },
        { key: 'trashCount', label: 'Trash', isTime: false },
        { key: 'limiterCount', label: 'Limiter', isTime: false },
        { key: 'repeaterCount', label: 'Repeater', isTime: false },
      ]

  const boxes = allMetrics
    .filter((def) => m[def.key] != null)
    .map((def) => {
      const raw = m[def.key]!
      const display = def.isTime ? formatDurationMs(raw) : String(raw)
      return `<div style="${boxStyle}"><div style="${labelStyle}">${def.label}</div><div style="${valueStyle}">${display}</div></div>`
    })

  if (boxes.length === 0) return null

  const defaultLabel = (node as any).getLabel?.() ?? ''
  return `${defaultLabel}<div style="display:flex;justify-content:center;gap:6px;margin-top:6px;">${boxes.join('')}</div>`
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
    content: `<span style="font-size:.75rem;font-weight:600;color:#f9fafb;background:#374151;padding:2px 6px;border-radius:9999px;white-space:nowrap;">${formatDurationMs(m.processTime)}</span>`,
  }
}

const nodeStatuses = ref<Record<string, 'error' | 'warning'>>({})

const errorIconSvg = '<svg style="width:40px;height:40px;" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" fill="#dc2626" stroke="#dc2626"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'
const warningIconSvg = '<svg style="width:40px;height:40px;" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" fill="#d97706" stroke="#d97706"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'

const handleErrorIconClick = (editorNodeId: string) => {
  const backendNodeId = resolveBackendId(editorNodeId)
  const correlationId = polling.processDetail.value?.id
  if (!correlationId) return

  const nodeData = nodesData.value[editorNodeId]
  failedMessageNodeId.value = backendNodeId
  failedMessageCorrelationId.value = correlationId
  failedMessageNodeName.value = nodeData?.name || editorNodeId
  failedMessageModalOpen.value = true
}

const handleFailedMessageUpdate = async () => {
  if (polling.isPolling.value) {
    polling.resetToFastPolling()
  } else if (polling.processDetail.value?.id) {
    await polling.fetchOnce(polling.processDetail.value.id)
    applyProcessDetailOverlays()
  }
}

const getOverlayTopLeft = (node: EditorNode) => {
  const status = nodeStatuses.value[node.id]
  if (!status) return null
  return {
    content: status === 'error' ? errorIconSvg : warningIconSvg,
    onClick: () => handleErrorIconClick(node.id),
  }
}

const overlayMethods = {
  getTopRightSlot: getOverlayTopRight,
  getTopLeftSlot: getOverlayTopLeft,
}

const getBreakpointOverlay = (node: EditorNode) => {
  const count = breakpointCounts.value[node.id]
  if (!count) return null
  return {
    content: `<span style="display:inline-flex;align-items:center;justify-content:center;min-width:36px;height:36px;padding:0 8px;font-size:1rem;font-weight:400;color:#fff;background:#dc2626;border-radius:9999px;box-shadow:0 2px 6px rgba(0,0,0,.3);">${count}</span>`,
    onClick: () => {
      selectedNode.value = node
      breakpointModalOpen.value = true
    },
  }
}

const getBreakpointActions = (node: EditorNode) => {
  const count = breakpointCounts.value[node.id] || 0
  if (!count) return []
  const backendNodeId = resolveBackendId(node.id)
  return [
    {
      id: 'approve-all',
      icon: '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
      label: 'Approve all',
      tooltip: 'Approve all',
      onClick: async () => {
        try {
          await approveAllBreakpoints(props.topologyId, backendNodeId)
          showToast('All breakpoint messages approved', 'success')
          await handleBreakpointUpdate()
        } catch (err) {
          console.error('Failed to approve all:', err)
          showToast('Failed to approve all messages', 'error')
        }
      },
    },
    {
      id: 'reject-all',
      icon: '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
      label: 'Reject all',
      tooltip: 'Reject all',
      onClick: async () => {
        try {
          await rejectAllBreakpoints(props.topologyId, backendNodeId)
          showToast('All breakpoint messages rejected', 'success')
          await handleBreakpointUpdate()
        } catch (err) {
          console.error('Failed to reject all:', err)
          showToast('Failed to reject all messages', 'error')
        }
      },
    },
  ]
}

const applyProcessDetailOverlays = async () => {
  const detail = polling.processDetail.value
  if (!detail) return

  const backendToEditor = buildBackendToEditorMap()

  const counts: Record<string, number | string> = {}
  const statuses: Record<string, 'error' | 'warning'> = {}
  const metrics: Record<string, NodeMetricsData> = {}

  for (const [backendNodeId, node] of Object.entries(detail.nodes)) {
    const editorId = backendToEditor.get(backendNodeId)
    if (!editorId) continue

    if (node.breakpointCount > 0) {
      counts[editorId] = node.breakpointCount
    }

    if (node.trashCount > 0) {
      statuses[editorId] = 'error'
    } else if (node.limiterCount > 0 || node.repeaterCount > 0) {
      statuses[editorId] = 'warning'
    }

    if (isProcessTimeRelevant(editorId)) {
      const m: NodeMetricsData = {}
      if (node.processTime != null) m.processTime = node.processTime
      if (node.requestTime != null) m.requestTime = node.requestTime
      if (node.trashCount > 0) m.trashCount = node.trashCount
      if (node.limiterCount > 0) m.limiterCount = node.limiterCount
      if (node.repeaterCount > 0) m.repeaterCount = node.repeaterCount
      if (Object.keys(m).length > 0) {
        metrics[editorId] = m
      }
    }
  }

  breakpointCounts.value = counts
  hasBreakpoints.value = Object.keys(counts).length > 0
  nodeStatuses.value = statuses
  nodeMetrics.value = metrics

  await editorCore.value?.updateNodeOverlays()
  editorCore.value?.refreshAllLabels()
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
    actions.push({
      id: 'copy-url',
      icon: '<rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>',
      label: 'Copy URL',
      tooltip: 'Copy URL',
      onClick: async (n: EditorNode) => {
        const url = getStartingPointUrl(n.id)
        await navigator.clipboard.writeText(url)
        showToast('URL copied to clipboard', 'success')
      }
    })
    return actions
  },
  getTopLeftSlot: getOverlayTopLeft,
}

const labelCustomization: LabelCustomizationMap = {
  Event: eventNodeActions,
  Webhook: eventNodeActions,
  Connector: { ...overlayMethods, getCustomLabel: (node: EditorNode) => buildMetricsLabelHtml(node, false) },
  'Custom Action': { ...overlayMethods, getCustomLabel: (node: EditorNode) => buildMetricsLabelHtml(node, true) },
  Batch: { ...overlayMethods, getCustomLabel: (node: EditorNode) => buildMetricsLabelHtml(node, false) },
  Breakpoint: {
    getTopLeftSlot: getBreakpointOverlay,
    getActions: getBreakpointActions,
  },
  Cron: {
    getFields: (node: EditorNode) => {
      const nodeData = nodesData.value[node.id]
      if (!nodeData) return []

      let nextRunValue = 'N/A'
      if (nodeData.crontab && props.topologyEnabled && nodeData.enabled) {
        const nextRunDate = getNextCronRun(nodeData.crontab)
        if (nextRunDate) {
          nextRunValue = formatDateTime(nextRunDate.toISOString())
        }
      }

      return [
        { label: 'Crontab', value: nodeData.crontab || 'Not set' },
        { label: 'Next run', value: nextRunValue },
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
    getTopLeftSlot: getOverlayTopLeft,
  }
}

const editorConfig = createConfig({
  mode: 'readonly',
  canvasHeight: '100%',
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
        nextRun: '',
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
    cachedSchema = structuredClone(schema)
    await editor.setActions(actions)
    initNodesData(editor, backendNodes)

    const disabledNodeIds = Object.entries(nodesData.value)
      .filter(([, data]) => !data.enabled)
      .map(([id]) => id)
    if (disabledNodeIds.length > 0) {
      await editor.setDisabledNodes(disabledNodeIds)
    }

    editor.zoomToFit()
    await loadInitialOverlays()
  } catch (error) {
    console.error('Failed to load topology data:', error)
  }
}

const loadInitialOverlays = async () => {
  try {
    const process = await fetchLatestProcess(props.topologyId)
    if (!process) return

    const detail = await polling.fetchOnce(process.id)
    if (!detail) return

    await applyProcessDetailOverlays()

    const hasBp = Object.values(detail.nodes).some(n => n.breakpointCount > 0)
    const isRunning = detail.status === 'IN PROGRESS'

    if (isRunning || hasBp) {
      const startNode = Object.values(nodesData.value).find(n =>
        ['event', 'webhook', 'cron'].includes(n.label.toLowerCase())
      )
      processStartNodeName.value = startNode?.name || 'topology'

      if (isRunning) {
        polling.startPollingWithId(process.id)
      }
    }
  } catch {
    // silently ignore init overlay errors
  }
}

const selectedNodeAsTask = computed<ScheduledTask | null>(() => {
  if (!selectedNode.value) return null

  const nodeData = nodesData.value[selectedNode.value.id]
  if (!nodeData) return null

  const nextRunDate = nodeData.crontab ? getNextCronRun(nodeData.crontab) : null
  return {
    id: resolveBackendId(selectedNode.value.id),
    nodeId: selectedNode.value.id,
    nodeStatus: nodeData.enabled,
    name: selectedNode.value.name || selectedNode.value.label,
    topology: '',
    topologyId: props.topologyId,
    crontab: nodeData.crontab || '',
    nextRun: nextRunDate,
    params: '',
    status: nodeData.enabled ? 'enabled' : 'disabled',
  }
})

const handleCrontabSave = async (backendNodeId: string, crontab: string) => {
  if (!selectedNode.value) return

  try {
    await updateCrontab(backendNodeId, crontab)

    const editorNodeId = selectedNode.value.id
    const nodeData = nodesData.value[editorNodeId]
    if (nodeData) {
      nodeData.crontab = crontab
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
    cachedSchema = structuredClone(schema)
    await editorCore.value.setActions(actions)
    initNodesData(editorCore.value, backendNodes)
    editorCore.value.zoomToFit()
  } catch (error) {
    console.error('Failed to reload topology schema:', error)
  }
}

defineExpose({ reloadSchema })

const processStartNodeName = ref<string | null>(null)
const processStartedAt = ref<string | null>(null)

const processStatus = computed(() => {
  const detail = polling.processDetail.value
  if (!detail) return polling.isPolling.value ? 'running' : null
  if (detail.status === 'COMPLETED') return 'success'
  if (detail.status === 'FAILED') return 'failed'
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
  polling.processDetail.value?.started || processStartedAt.value
)

const processEndTime = computed(() => {
  return polling.processDetail.value?.finished || null
})

const processDuration = computed(() => {
  const d = polling.processDetail.value?.duration
  if (!d) return null
  return formatDurationMs(d)
})

const showProcessPanel = computed(() => !!processStartNodeName.value)

const dismissProcessPanel = () => {
  processStartNodeName.value = null
  processStartedAt.value = null
  polling.stopPolling()
  polling.processDetail.value = null
  polling.processCompleted.value = false
  nodeStatuses.value = {}
  breakpointCounts.value = {}
  hasBreakpoints.value = false
}

const handleRunProcess = async (jsonData: string) => {
  if (!selectedNode.value) return

  try {
    if (hasBreakpoints.value) {
      await rejectAllBreakpoints(props.topologyId)
      breakpointCounts.value = {}
      hasBreakpoints.value = false
      await editorCore.value?.updateNodeOverlays()
    }

    const backendId = resolveBackendId(selectedNode.value.id)
    await runProcess(
      props.topologyId,
      backendId,
      selectedNode.value.name || selectedNode.value.label,
      jsonData
    )
    nodeStatuses.value = {}
    breakpointCounts.value = {}
    processStartNodeName.value = selectedNode.value.name || selectedNode.value.label
    processStartedAt.value = new Date().toISOString()
    polling.startPolling()
    emit('process-run')
  } catch (error) {
    console.error('Failed to run process:', error)
  }
}

const handleBreakpointUpdate = async () => {
  if (polling.isPolling.value) {
    polling.resetToFastPolling()
  } else if (polling.processDetail.value?.id) {
    await polling.fetchOnce(polling.processDetail.value.id)
    await applyProcessDetailOverlays()
  }
}

const handlePositionChanged = async () => {
  if (!editorCore.value || !cachedSchema) return
  try {
    const positions = editorCore.value.getNodePositions()
    const updated = structuredClone(cachedSchema)

    if (updated.nodes && Array.isArray(updated.nodes)) {
      for (const node of updated.nodes) {
        const pos = positions[node.id]
        if (pos) {
          node.position = { x: pos.x, y: pos.y }
        }
      }
    }

    await saveTopologySchema(props.topologyId, updated)
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

watch(() => polling.processDetail.value, async () => {
  await applyProcessDetailOverlays()
})

watch(() => polling.processCompleted.value, async (completed) => {
  if (completed) {
    await applyProcessDetailOverlays()
  }
})

watch(() => props.topologyEnabled, () => {
  if (!editorCore.value) return
  for (const [editorId, data] of Object.entries(nodesData.value)) {
    if (data.label.toLowerCase() === 'cron') {
      editorCore.value.refreshNodeLabel(editorId)
    }
  }
})

watch(() => props.refreshKey, () => {
  if (polling.isPolling.value) return
  nodeStatuses.value = {}
  breakpointCounts.value = {}
  const startNode = Object.values(nodesData.value).find(n =>
    ['event', 'webhook', 'cron'].includes(n.label.toLowerCase())
  )
  processStartNodeName.value = startNode?.name || 'topology'
  processStartedAt.value = new Date().toISOString()
  polling.startPolling()
})
</script>

<template>
  <div class="relative h-full">
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
            <div v-if="polling.processDetail.value?.id" class="text-gray-500 dark:text-gray-400">
              Correl. ID:
              <CopyValue :value="polling.processDetail.value.id">
                <span class="font-mono">{{ polling.processDetail.value.id.slice(0, 16) }}...</span>
              </CopyValue>
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
      :has-breakpoint-messages="hasBreakpoints"
      @run="handleRunProcess"
    />

    <BreakpointModal
      v-model="breakpointModalOpen"
      :node-name="selectedNode?.name || selectedNode?.label || ''"
      :node-id="selectedNode ? resolveBackendId(selectedNode.id) : ''"
      :topology-id="topologyId"
      @update="handleBreakpointUpdate"
    />

    <FailedMessageModal
      v-model="failedMessageModalOpen"
      :topology-id="topologyId"
      :node-id="failedMessageNodeId"
      :correlation-id="failedMessageCorrelationId"
      :node-name="failedMessageNodeName"
      @update="handleFailedMessageUpdate"
    />
  </div>
</template>

<style scoped>
:deep(.rete-editor-wrapper) {
  height: 100%;
}

:deep(.property-control) {
  display: none;
}
</style>

