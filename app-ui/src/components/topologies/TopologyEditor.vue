<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { ReteEditorKit } from 'rete-editor'
import type { EditorCore, LabelCustomizationMap } from 'rete-editor'
import CronSettingsModal from '@/components/scheduled-tasks/CronSettingsModal.vue'
import RunProcessModal from '@/components/topologies/RunProcessModal.vue'
import BreakpointModal from '@/components/topologies/BreakpointModal.vue'
import FailedMessageModal from '@/components/topologies/FailedMessageModal.vue'
import WebhookSubscribeModal from '@/components/topologies/WebhookSubscribeModal.vue'
import PrefetchSettingsModal from '@/components/topologies/PrefetchSettingsModal.vue'
import {
  listWebhookConfigs,
  subscribeWebhookConfig,
  unsubscribeWebhookConfig,
  deleteWebhookConfig,
  cascadeWebhookConfigs,
  buildWebhookCallbackUrl,
  type WebhookConfigItem,
} from '@/services/webhookConfigService'
import { STARTING_POINT_URL } from '@/config'
import CopyValue from '@/components/ui/CopyValue.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import type { BadgeVariant } from '@/components/ui/StatusBadge.vue'
import { useCronNodeActions } from '@/composables/useCronNodeActions'
import { getNextCronRun } from '@/utils/cronParser'
import { useToast } from '@/composables/useToast'
import { useDateFormat } from '@/composables/useDateFormat'
import { useProcessPolling } from '@/composables/useProcessPolling'
import {
  fetchTopologySchema,
  saveTopologySchema,
  fetchTopologyDetail,
  republishTopology,
} from '@/services/topologiesService'
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
  topologyName: string
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
  application?: string | null
  sdk?: string | null
  prefetch?: number
}

const editorCore = ref<EditorCore>()
const cronSettingsModalOpen = ref(false)
const webhookSubscribeModalOpen = ref(false)
const prefetchSettingsModalOpen = ref(false)
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
const nodeApplications = ref<Record<string, { application: string; sdk: string }>>({})
// Backend prefetch keyed by editor node id. Surfaced in the label and
// settings modal for Connector / Batch / Custom Action types — see
// `getNodePrefetch` callers below.
const nodePrefetch = ref<Record<string, number>>({})

// Topology-level "stale bridge" flag. Set when API edits (prefetch, ...)
// persisted to Mongo haven't yet been propagated to the running consumer.
// Cleared by republishing the topology.
const bridgeOutOfSync = ref(false)
const republishing = ref(false)

const refreshTopologyMeta = async () => {
  try {
    const detail = await fetchTopologyDetail(props.topologyId)
    bridgeOutOfSync.value = (detail as unknown as { bridgeOutOfSync?: boolean }).bridgeOutOfSync === true
  } catch (error) {
    console.error('Failed to load topology detail:', error)
  }
}

const webhookConfigs = ref<WebhookConfigItem[]>([])
const webhookConfigsByNodeName = computed<Record<string, WebhookConfigItem>>(() => {
  const map: Record<string, WebhookConfigItem> = {}
  for (const cfg of webhookConfigs.value) {
    if (!cfg.orphan) {
      map[cfg.nodeName] = cfg
    }
  }
  return map
})
const orphanWebhookConfigs = computed(() => webhookConfigs.value.filter((c) => c.orphan))

const refreshWebhookConfigs = async () => {
  if (!props.topologyName) {
    webhookConfigs.value = []
    return
  }
  try {
    webhookConfigs.value = await listWebhookConfigs(props.topologyName)
  } catch (error) {
    console.error('Failed to load webhook configs:', error)
    webhookConfigs.value = []
  }
}

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

// Strict starting-point URL: identifies the topology + node by Mongo
// ObjectIds and is therefore pinned to one specific topology version. Useful
// when integrators want to lock a tester / cron to a single deployed build.
const getStartingPointUrl = (editorNodeId: string): string => {
  const backendId = resolveBackendId(editorNodeId)
  return `${STARTING_POINT_URL}/topologies/${props.topologyId}/nodes/${backendId}/run`
}

// Name-based starting-point URL: matches the starting-point's
// `/run-by-name` route and resolves to whichever topology version is
// currently active. This is the URL we want integrators to share by default
// — copy actions in the editor expose it as the primary "Copy URL" action.
const getStartingPointUrlByName = (node: EditorNode): string => {
  const topology = encodeURIComponent(props.topologyName || '')
  const nodeName = encodeURIComponent(node.name || '')
  return `${STARTING_POINT_URL}/topologies/${topology}/nodes/${nodeName}/run-by-name`
}


const EXCLUDED_PROCESS_TIME_LABELS = new Set(['event', 'webhook', 'cron', 'breakpoint'])

const isProcessTimeRelevant = (editorId: string): boolean => {
  const label = nodesData.value[editorId]?.label?.toLowerCase()
  return !label || !EXCLUDED_PROCESS_TIME_LABELS.has(label)
}

const buildMetricsLabelHtml = (node: EditorNode, isCustomAction: boolean): string | null => {
  const m = nodeMetrics.value[node.id]

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

  const boxes = m
    ? allMetrics
        .filter((def) => m[def.key] != null)
        .map((def) => {
          const raw = m[def.key]!
          const display = def.isTime ? formatDurationMs(raw) : String(raw)
          return `<div style="${boxStyle}"><div style="${labelStyle}">${def.label}</div><div style="${valueStyle}">${display}</div></div>`
        })
    : []

  const defaultLabel = (node as any).getLabel?.() ?? ''
  const prefetch = nodePrefetch.value[node.id] ?? 1
  // Inline Prefetch attribute + Settings action so the whole header (Connector,
  // Worker, Prefetch, Settings) lives on a single row above the metric boxes.
  // The Settings anchor uses `data-prefetch-settings="<nodeId>"` and is wired up
  // via document-level click delegation in this component, because innerHTML
  // can't carry Vue listeners.
  const sep = '<span style="opacity:.4;margin:0 4px;">|</span>'
  const prefetchHtml =
    `${sep}<span style="opacity:.6;">Prefetch:</span> ${prefetch} ` +
    `<a data-prefetch-settings="${node.id}" ` +
    `class="ml-2 cursor-pointer underline text-primary-600 hover:text-primary-800 dark:text-primary-400 dark:hover:text-primary-300 transition-colors">Settings</a>`

  const header = `${defaultLabel}${prefetchHtml}`

  if (boxes.length === 0) return header

  return `${header}<div style="display:flex;justify-content:center;gap:6px;margin-top:6px;">${boxes.join('')}</div>`
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
      tooltip: 'Copy URL (resolves to the currently active topology version)',
      onClick: async (n: EditorNode) => {
        if (!n.name) {
          showToast('Event has no name yet — save the topology first', 'warning')
          return
        }
        try {
          const url = getStartingPointUrlByName(n)
          await navigator.clipboard.writeText(url)
          showToast('URL copied to clipboard', 'success')
        } catch (e) {
          showToast((e as Error).message, 'error')
        }
      }
    })
    actions.push({
      id: 'copy-strict-url',
      icon: '<rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M9 14l2 2 4-4"/>',
      label: 'Copy strict version URL',
      tooltip: 'Copy URL pinned to this exact topology version (uses Mongo IDs)',
      // Strict version is power-user only — keep the inline label clean and
      // expose it solely from the right-click dropdown.
      hideInLabel: true,
      onClick: async (n: EditorNode) => {
        try {
          const url = getStartingPointUrl(n.id)
          await navigator.clipboard.writeText(url)
          showToast('Strict version URL copied to clipboard', 'success')
        } catch (e) {
          showToast((e as Error).message, 'error')
        }
      }
    })
    return actions
  },
  getTopLeftSlot: getOverlayTopLeft,
}

// Webhook subscribe modal state. The user only ever sees subscribe /
// unsubscribe — the underlying WebhookConfig document is created lazily on
// the backend during the first subscribe call.
const selectedWebhookNodeName = ref<string>('')
const selectedWebhookApplication = ref<string>('')
const selectedWebhookInitialParameters = ref<Record<string, unknown> | null>(null)

const openWebhookSubscribeModal = (node: EditorNode) => {
  const ctx = nodeApplications.value[node.name] ?? { application: '', sdk: '' }
  const existing = webhookConfigsByNodeName.value[node.name]
  selectedWebhookNodeName.value = node.name
  selectedWebhookApplication.value = ctx.application
  selectedWebhookInitialParameters.value = existing?.parameters
    ? (existing.parameters as Record<string, unknown>)
    : null
  webhookSubscribeModalOpen.value = true
}

const handleWebhookCopyUrl = async (node: EditorNode) => {
  // The callback URL is only meaningful once a token has been issued by the
  // SDK during subscribe. Without it we have nothing usable to copy — bail
  // with a hint and refuse to fall back to the id-based starting-point URL
  // (which targets engineers running ad-hoc topologies, not external
  // webhook providers).
  const cfg = webhookConfigsByNodeName.value[node.name]
  if (!cfg?.token) {
    showToast('Subscribe the webhook first to generate a callback URL', 'warning')
    return
  }
  try {
    const url = buildWebhookCallbackUrl(props.topologyName, node.name, cfg.token)
    await navigator.clipboard.writeText(url)
    showToast('URL copied to clipboard', 'success')
  } catch (e) {
    showToast((e as Error).message, 'error')
  }
}

const handleWebhookUnsubscribe = async (node: EditorNode) => {
  try {
    await unsubscribeWebhookConfig(props.topologyName, node.name)
    showToast('Webhook unsubscribed', 'success')
    await refreshWebhookConfigs()
    await applyEventDisabledState()
    editorCore.value?.refreshNodeLabel(node.id)
  } catch (error) {
    console.error('Unsubscribe failed:', error)
    showToast(`Unsubscribe failed: ${(error as Error).message}`, 'error')
  }
}

const handleWebhookSubscribed = async () => {
  webhookSubscribeModalOpen.value = false
  selectedWebhookNodeName.value = ''
  selectedWebhookApplication.value = ''
  selectedWebhookInitialParameters.value = null
  await refreshWebhookConfigs()
  if (editorCore.value) {
    await applyEventDisabledState()
    for (const id of Object.keys(nodesData.value)) {
      editorCore.value.refreshNodeLabel(id)
    }
  }
}

const webhookNodeActions = {
  getFields: (node: EditorNode) => {
    // Two-state UX: the user does not need to know about the WebhookConfig
    // intent layer. Either the webhook is subscribed against the external
    // API (live token + Webhook doc), or it is not.
    const cfg = webhookConfigsByNodeName.value[node.name]
    return [
      { label: 'Status', value: cfg?.registered ? 'Subscribed' : 'Off' },
    ]
  },
  getActions: (node: EditorNode) => {
    const cfg = webhookConfigsByNodeName.value[node.name]
    const actions: any[] = []

    if (cfg?.registered) {
      // One-click unsubscribe. Re-subscribing with different parameters means
      // unsubscribe → Subscribe again (which re-opens the modal) — we
      // deliberately do not expose a separate "edit parameters while
      // subscribed" path to keep the UI in sync with the upstream service.
      actions.push({
        id: 'webhook-unsubscribe',
        icon: '<path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm3.707 8.707-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 1 1 1.414-1.414L11 12.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>',
        label: 'Unsubscribe',
        tooltip: 'Unsubscribe from external API',
        onClick: () => handleWebhookUnsubscribe(node),
      })
      actions.push({
        id: 'webhook-copy-url',
        icon: '<rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>',
        label: 'Copy URL',
        tooltip: 'Copy callback URL',
        onClick: () => handleWebhookCopyUrl(node),
      })
    } else {
      actions.push({
        id: 'webhook-subscribe',
        icon: '<path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm3.707 8.707-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 1 1 1.414-1.414L11 12.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"/>',
        label: 'Subscribe',
        tooltip: 'Subscribe to external API',
        onClick: () => openWebhookSubscribeModal(node),
      })
    }

    return actions
  },
  getTopLeftSlot: getOverlayTopLeft,
}

const selectedPrefetchNodeId = ref<string>('')
const selectedPrefetchNodeName = ref<string>('')
const selectedPrefetchValue = ref<number>(1)

const openPrefetchSettings = (node: EditorNode) => {
  const backendId = resolveBackendId(node.id)
  selectedPrefetchNodeId.value = backendId
  selectedPrefetchNodeName.value = node.name || node.label
  selectedPrefetchValue.value = nodePrefetch.value[node.id] ?? 1
  prefetchSettingsModalOpen.value = true
}

// The prefetch "Settings" anchor is rendered via innerHTML inside the rete
// editor label panel, so Vue listeners can't be attached directly. Use
// document-level click delegation: when the click target matches
// `[data-prefetch-settings]`, look up the node by id and open the modal.
const handlePrefetchSettingsClick = (event: MouseEvent) => {
  const target = event.target as HTMLElement | null
  if (!target) return
  const link = target.closest('[data-prefetch-settings]') as HTMLElement | null
  if (!link) return
  event.preventDefault()
  event.stopPropagation()
  const nodeId = link.getAttribute('data-prefetch-settings')
  if (!nodeId) return
  // Prefer the currently selected node (matches the rendered label) but fall
  // back to looking it up from the editor core for safety.
  if (selectedNode.value?.id === nodeId) {
    openPrefetchSettings(selectedNode.value)
    return
  }
  const editorNode = editorCore.value?.getNode?.(nodeId) as EditorNode | undefined
  if (editorNode) openPrefetchSettings(editorNode)
}

onMounted(() => {
  document.addEventListener('click', handlePrefetchSettingsClick)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handlePrefetchSettingsClick)
})

const handlePrefetchSaved = async (republished: boolean) => {
  // Pull fresh node + topology state so the label and banner reflect what
  // the backend actually has. We do this even if the republish failed
  // (republished === false) so the banner becomes visible to nudge the
  // user.
  try {
    const backendNodes = await fetchBackendNodes()
    if (editorCore.value) {
      initNodesData(editorCore.value, backendNodes)
    }
    await refreshTopologyMeta()
    if (republished) {
      bridgeOutOfSync.value = false
    }
    if (editorCore.value) {
      for (const id of Object.keys(nodesData.value)) {
        editorCore.value.refreshNodeLabel(id)
      }
      // Connector/Batch/Custom Action labels aren't tracked in nodesData,
      // so refresh every node id we have prefetch for as well.
      for (const id of Object.keys(nodePrefetch.value)) {
        editorCore.value.refreshNodeLabel(id)
      }
    }
  } catch (error) {
    console.error('Failed to refresh after prefetch save:', error)
  }
}

const handleRepublishNow = async () => {
  if (republishing.value) return
  republishing.value = true
  try {
    await republishTopology(props.topologyId)
    showToast('Bridge republished', 'success')
    bridgeOutOfSync.value = false
    await refreshTopologyMeta()
  } catch (error) {
    console.error('Republish failed:', error)
    showToast(`Republish failed: ${(error as Error).message}`, 'error')
  } finally {
    republishing.value = false
  }
}

// Worker-consuming node types where setting prefetch makes sense. Mirrors
// the allow-list in the backend NodeManager::applyPrefetch.
const PREFETCH_ICON = '<path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>'

const buildPrefetchAction = (node: EditorNode) => ({
  id: 'prefetch-settings',
  icon: PREFETCH_ICON,
  label: 'Settings',
  tooltip: 'Prefetch settings',
  onClick: () => openPrefetchSettings(node),
})

const workerNodeCustomization = (isCustomAction: boolean) => ({
  ...overlayMethods,
  // Prefetch attribute + Settings link are baked into the customLabel HTML so
  // they sit on the first row alongside the Connector / Worker attributes.
  // We still expose the action via getActions for the right-click context
  // menu (with hideInLabel: true so it doesn't render as a separate label row).
  getCustomLabel: (node: EditorNode) => buildMetricsLabelHtml(node, isCustomAction),
  getActions: (node: EditorNode) => [{ ...buildPrefetchAction(node), hideInLabel: true }],
})

const labelCustomization: LabelCustomizationMap = {
  Event: eventNodeActions,
  Webhook: webhookNodeActions,
  Connector: workerNodeCustomization(false),
  'Custom Action': workerNodeCustomization(true),
  Batch: workerNodeCustomization(false),
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
  nodePrefetch.value = {}

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
      if (backend.application || backend.sdk) {
        nodeApplications.value[editorNode.name] = {
          application: backend.application ?? '',
          sdk: backend.sdk ?? '',
        }
      }
      if (typeof backend.prefetch === 'number' && backend.prefetch >= 1) {
        nodePrefetch.value[editorNode.id] = backend.prefetch
      }
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

// Computes the full set of editor node ids that should appear visually
// disabled (greyed-out) and pushes it to the rete editor. Two sources feed
// into the set:
//  1. cron / event nodes whose backend `enabled` flag is false,
//  2. webhook nodes whose `WebhookConfig` is missing OR has `registered=false`
//     — i.e. anything that isn't currently subscribed against the upstream
//     API. This way an unsubscribed webhook reads as "off" at a glance, the
//     same way a paused cron does.
const applyEventDisabledState = async () => {
  if (!editorCore.value) return
  const ids = new Set<string>()
  for (const [id, data] of Object.entries(nodesData.value)) {
    const label = data.label.toLowerCase()
    if (!data.enabled) {
      ids.add(id)
      continue
    }
    if (label === 'webhook') {
      const cfg = webhookConfigsByNodeName.value[data.name]
      if (!cfg || !cfg.registered) {
        ids.add(id)
      }
    }
  }
  await editorCore.value.setDisabledNodes([...ids])
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
    await Promise.all([refreshWebhookConfigs(), refreshTopologyMeta()])

    await applyEventDisabledState()

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
      polling.startPollingWithId(process.id)
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
    await refreshTopologyMeta()
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

const statusBadgeVariant = computed<BadgeVariant>(() => {
  switch (processStatus.value) {
    case 'running': return 'blue'
    case 'success': return 'green'
    case 'failed': return 'red'
    default: return 'gray'
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
    const runResult = await runProcess(
      props.topologyId,
      backendId,
      selectedNode.value.name || selectedNode.value.label,
      jsonData
    )
    nodeStatuses.value = {}
    breakpointCounts.value = {}
    processStartNodeName.value = selectedNode.value.name || selectedNode.value.label
    processStartedAt.value = new Date().toISOString()

    const correlationId = runResult.find((r) => r.correlationId)?.correlationId
    if (correlationId) {
      polling.startPollingWithId(correlationId)
    } else {
      polling.startPolling()
    }
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

watch(() => props.topologyEnabled, async (enabled, previous) => {
  if (!editorCore.value) return
  for (const [editorId, data] of Object.entries(nodesData.value)) {
    if (data.label.toLowerCase() === 'cron') {
      editorCore.value.refreshNodeLabel(editorId)
    }
  }

  // Cascade webhook subscribe / unsubscribe when the topology is toggled.
  if (props.topologyName && enabled !== previous && webhookConfigs.value.length > 0) {
    try {
      const results = await cascadeWebhookConfigs(props.topologyName, enabled)
      const failures = results.filter((r) => r.status === 'error')
      if (failures.length > 0) {
        showToast(
          `Webhook cascade ${enabled ? 'subscribe' : 'unsubscribe'} finished with ${failures.length} error(s)`,
          'error',
        )
      } else if (results.length > 0) {
        showToast(`Webhooks ${enabled ? 'subscribed' : 'unsubscribed'}`, 'success')
      }
      await refreshWebhookConfigs()
      await applyEventDisabledState()
      for (const id of Object.keys(nodesData.value)) {
        editorCore.value.refreshNodeLabel(id)
      }
    } catch (error) {
      console.error('Webhook cascade failed:', error)
      showToast(`Webhook cascade failed: ${(error as Error).message}`, 'error')
    }
  }
})

const cleanupOrphan = async (orphan: WebhookConfigItem) => {
  try {
    await deleteWebhookConfig(orphan.topologyName, orphan.nodeName)
    showToast(`Removed orphan webhook for "${orphan.nodeName}"`, 'success')
    await refreshWebhookConfigs()
    await applyEventDisabledState()
  } catch (error) {
    console.error('Failed to clean up orphan webhook:', error)
    showToast(`Cleanup failed: ${(error as Error).message}`, 'error')
  }
}

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
              <StatusBadge :variant="statusBadgeVariant">{{ statusLabel }}</StatusBadge>
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

    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 -translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
    >
      <div
        v-if="bridgeOutOfSync"
        class="absolute top-3 right-3 z-50 max-w-md rounded-lg border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm shadow-lg dark:border-yellow-600 dark:bg-yellow-900/40 dark:text-yellow-100"
      >
        <div class="flex items-start gap-3">
          <div class="flex-1 space-y-1.5">
            <div class="font-semibold text-yellow-800 dark:text-yellow-200">
              Bridge is out of sync
            </div>
            <p class="text-xs text-yellow-700 dark:text-yellow-300">
              Configuration changes (e.g. prefetch) are saved but the running consumer
              still uses the old values. Republish the topology to apply them.
            </p>
            <button
              class="text-xs font-semibold text-yellow-800 underline hover:text-yellow-900 dark:text-yellow-200 dark:hover:text-yellow-100"
              :disabled="republishing"
              @click="handleRepublishNow"
            >
              {{ republishing ? 'Republishing…' : 'Republish now' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 -translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
    >
      <div
        v-if="orphanWebhookConfigs.length > 0"
        class="absolute right-3 z-50 max-w-md rounded-lg border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm shadow-lg dark:border-yellow-600 dark:bg-yellow-900/40 dark:text-yellow-100"
        :class="bridgeOutOfSync ? 'top-32' : 'top-3'"
      >
        <div class="flex items-start gap-3">
          <div class="flex-1 space-y-1.5">
            <div class="font-semibold text-yellow-800 dark:text-yellow-200">
              Orphan webhook registrations
            </div>
            <p class="text-xs text-yellow-700 dark:text-yellow-300">
              These external registrations are still active but no longer have a matching node in this topology.
              Clean them up or recreate the corresponding node to keep the integration consistent.
            </p>
            <ul class="space-y-1">
              <li
                v-for="orphan in orphanWebhookConfigs"
                :key="`${orphan.nodeName}-${orphan.eventName}`"
                class="flex items-center justify-between gap-2 rounded-md bg-white/40 px-2 py-1 dark:bg-yellow-900/40"
              >
                <span class="text-xs font-mono text-yellow-900 dark:text-yellow-100">
                  {{ orphan.nodeName }} → {{ orphan.eventName }}
                </span>
                <button
                  class="text-xs font-semibold text-yellow-800 underline hover:text-yellow-900 dark:text-yellow-200 dark:hover:text-yellow-100"
                  @click="cleanupOrphan(orphan)"
                >
                  Cleanup
                </button>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </Transition>

    <CronSettingsModal
      v-model="cronSettingsModalOpen"
      :task="selectedNodeAsTask"
      @save="handleCrontabSave"
    />

    <WebhookSubscribeModal
      v-model="webhookSubscribeModalOpen"
      :topology-name="topologyName"
      :node-name="selectedWebhookNodeName"
      :application="selectedWebhookApplication"
      :initial-parameters="selectedWebhookInitialParameters"
      @subscribed="handleWebhookSubscribed"
    />

    <PrefetchSettingsModal
      v-model="prefetchSettingsModalOpen"
      :node-id="selectedPrefetchNodeId"
      :node-name="selectedPrefetchNodeName"
      :topology-id="topologyId"
      :current-prefetch="selectedPrefetchValue"
      @saved="handlePrefetchSaved"
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
      modal-id="failed-message-modal-editor"
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

