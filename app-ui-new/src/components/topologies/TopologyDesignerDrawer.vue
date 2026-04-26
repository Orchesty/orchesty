<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { Drawer } from 'flowbite'
import type { DrawerInterface } from 'flowbite'
import Button from '@/components/ui/Button.vue'
import Modal from '@/components/ui/Modal.vue'
import { ReteEditorKit } from 'rete-editor'
import type { EditorCore, EditorConfig } from 'rete-editor'
import { useToast } from '@/composables/useToast'
import { topologyEditorService } from '@/services/topologyEditorService'
import { fetchTopologySchema, saveTopologySchema } from '@/services/topologiesService'
import { listWebhookConfigs, type WebhookConfigItem } from '@/services/webhookConfigService'

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

const webhookWarningOpen = ref(false)
const webhookWarningMissing = ref<WebhookConfigItem[]>([])
let pendingSavePayload: ReturnType<EditorCore['exportGraph']> | null = null

const collectWebhookNodeNames = (): Set<string> => {
  if (!editorCore.value) return new Set()
  const names = new Set<string>()
  const nodes = editorCore.value.getNodes() as Array<{ name?: string; baseLabel?: string }>
  for (const node of nodes) {
    if ((node.baseLabel || '').toLowerCase() === 'webhook' && node.name) {
      names.add(node.name)
    }
  }
  return names
}

// Node kinds that are uniquely identified by `name` at runtime — `Event`
// (start) and `Cron` carry a user-provided identifier, `Webhook` carries
// `${application}.${event}` assigned by the picker. None of them function
// without one: the start-event URL, the cron schedule binding, and the
// webhook callback all key on this string. We refuse to save a schema
// containing any unnamed instance so the user is forced to fix it now,
// before a half-broken topology is published.
const NAMED_REQUIRED_LABELS = new Set(['event', 'cron', 'webhook'])

interface UnnamedNode { id: string; label: string }

const findUnnamedRequiredNodes = (): UnnamedNode[] => {
  if (!editorCore.value) return []
  const offenders: UnnamedNode[] = []
  const nodes = editorCore.value.getNodes() as Array<{ id: string; name?: string; baseLabel?: string }>
  for (const node of nodes) {
    const label = (node.baseLabel || '').trim()
    if (!NAMED_REQUIRED_LABELS.has(label.toLowerCase())) continue
    if ((node.name ?? '').trim() === '') {
      offenders.push({ id: node.id, label })
    }
  }
  return offenders
}

const formatUnnamedSummary = (offenders: UnnamedNode[]): string => {
  // Group by base label so the toast reads like
  // "1 Event, 2 Webhooks" rather than dumping ids.
  const counts = new Map<string, number>()
  for (const o of offenders) {
    counts.set(o.label, (counts.get(o.label) ?? 0) + 1)
  }
  return Array.from(counts.entries())
    .map(([label, count]) => `${count} ${label}${count > 1 ? 's' : ''}`)
    .join(', ')
}

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

  // The webhook picker is now handled directly by rete-editor: dropping a
  // Webhook node and double-clicking (or right-click → "Pick subscription")
  // opens the actions submenu filtered to `type: 'webhook'`. Selecting an
  // entry sets both the node action and its name (`app.event`) automatically.
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

const persistSchema = async (workflow: ReturnType<EditorCore['exportGraph']>) => {
  try {
    const result = await saveTopologySchema(props.topologyId, workflow)
    showToast('Topology saved successfully', 'success', 3000)
    emit('save', result)
  } catch (error) {
    console.error('Failed to save topology:', error)
    showToast('Failed to save topology', 'error', 3000)
  }
}

const handleSave = async () => {
  if (!editorCore.value) {
    showToast('Editor not initialized', 'error', 3000)
    return
  }

  // Reject saves that contain Event / Cron / Webhook nodes without a
  // name. Cron and Event names are typed inline; Webhook names come from
  // the subscription picker (double-click on the node or right-click →
  // "Pick subscription"). The toast points the user to whichever flow
  // applies so they can fix it before re-saving.
  const unnamed = findUnnamedRequiredNodes()
  if (unnamed.length > 0) {
    showToast(
      `Cannot save: ${formatUnnamedSummary(unnamed)} without a name. ` +
        'Set a name for each Event / Cron node and pick a subscription for each Webhook.',
      'error',
      6000,
    )
    return
  }

  const workflow = editorCore.value.exportGraph()

  // Detect webhook configs whose owning node is being deleted or renamed.
  try {
    const configs = await listWebhookConfigs(props.topologyName)
    if (configs.length > 0) {
      const remainingWebhookNames = collectWebhookNodeNames()
      const missing = configs.filter(
        (cfg) => !cfg.orphan && !remainingWebhookNames.has(cfg.nodeName),
      )
      if (missing.length > 0) {
        pendingSavePayload = workflow
        webhookWarningMissing.value = missing
        webhookWarningOpen.value = true
        return
      }
    }
  } catch (error) {
    console.warn('Could not validate webhook configs before save:', error)
  }

  await persistSchema(workflow)
}

const confirmSaveWithWebhookWarning = async () => {
  if (!pendingSavePayload) {
    webhookWarningOpen.value = false
    return
  }
  const payload = pendingSavePayload
  pendingSavePayload = null
  webhookWarningOpen.value = false
  await persistSchema(payload)
}

const cancelSaveWithWebhookWarning = () => {
  pendingSavePayload = null
  webhookWarningMissing.value = []
  webhookWarningOpen.value = false
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

  <Modal
    :model-value="webhookWarningOpen"
    id="webhook-rename-warning-modal"
    title="Webhook subscriptions will become orphaned"
    size="md"
    @update:model-value="(v) => v ? webhookWarningOpen = true : cancelSaveWithWebhookWarning()"
  >
    <div class="space-y-4">
      <p class="text-sm text-gray-700 dark:text-gray-200">
        The following webhook node(s) referenced in saved configurations are being renamed or deleted.
        Saving will <strong>not</strong> update the active external registrations — the existing webhooks will keep
        delivering events to the old callback URL until you clean them up in the readonly view of the topology.
      </p>
      <ul class="space-y-1 rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-sm dark:border-yellow-700 dark:bg-yellow-900/30">
        <li
          v-for="cfg in webhookWarningMissing"
          :key="`${cfg.nodeName}-${cfg.eventName}`"
          class="flex items-center justify-between gap-2 font-mono text-yellow-900 dark:text-yellow-100"
        >
          <span>{{ cfg.nodeName }} → {{ cfg.eventName }}</span>
          <span v-if="cfg.registered" class="rounded bg-yellow-200 px-2 py-0.5 text-xs text-yellow-900 dark:bg-yellow-700 dark:text-yellow-50">
            subscribed
          </span>
        </li>
      </ul>
      <p class="text-xs text-gray-500 dark:text-gray-400">
        Recommended: cancel, rename / re-subscribe webhooks from the topology detail page, then save again.
      </p>
    </div>

    <template #footer-actions>
      <Button variant="outline" @click="cancelSaveWithWebhookWarning">Cancel</Button>
      <Button variant="primary" @click="confirmSaveWithWebhookWarning">
        Save anyway
      </Button>
    </template>
  </Modal>
</template>

