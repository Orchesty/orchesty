<script setup lang="ts">
import { computed, ref } from 'vue'
import { ChevronDown, ChevronUp } from 'lucide-vue-next'
import type { AuditResultStatus, ICheckpointSnapshot, IEntityRun } from '@/types/trace'

interface Props {
  run: IEntityRun
}

const props = defineProps<Props>()

const showEntryPayload = ref(false)
const showExitPayload = ref(false)
const expandedSteps = ref<Record<number, boolean>>({})

const formatTime = (time: string | null): string => {
  if (!time) return '—'
  // Loki returns nanosecond unix timestamps as strings.
  const ns = Number(time)
  if (!Number.isFinite(ns)) return time
  const ms = Math.floor(ns / 1_000_000)
  return new Date(ms).toLocaleString()
}

// XSS-safe payload rendering: always JSON.stringify, never v-html. The bridge
// applies a strict allowlist + redaction so values are bounded, but the UI must
// still treat the strings as untrusted text.
const formatPayload = (payload: unknown): string => {
  if (payload === null || payload === undefined) return ''
  if (typeof payload === 'string') {
    try {
      return JSON.stringify(JSON.parse(payload), null, 2)
    } catch {
      return payload
    }
  }
  return JSON.stringify(payload, null, 2)
}

const isMarkerOnly = (snap: ICheckpointSnapshot | null): boolean => {
  if (!snap) return false
  return snap.payload === null || snap.payload === undefined
}

interface PayloadFlags {
  truncated: boolean
  invalidJson: boolean
  originalSizeBytes: number | null
}

const payloadFlags = (snap: ICheckpointSnapshot | null): PayloadFlags => {
  const flags: PayloadFlags = { truncated: false, invalidJson: false, originalSizeBytes: null }
  if (!snap || typeof snap.payload !== 'object' || snap.payload === null) return flags
  const p = snap.payload as Record<string, unknown>
  if (p._truncated === true) flags.truncated = true
  if (p._invalidJson === true) flags.invalidJson = true
  if (typeof p._originalSizeBytes === 'number') flags.originalSizeBytes = p._originalSizeBytes
  return flags
}

const entryPayloadText = computed(() => formatPayload(props.run.entry?.payload))
const exitPayloadText = computed(() => formatPayload(props.run.exit?.payload))

// Multi-entity correlation: a single run may include sibling entity
// checkpoints (e.g. an Order's run has an Order entry/exit + several LineItem
// steps). Group steps by `nodeName` so the user can keep context — see
// "Trade-offs / Limitations" section of the audit checkpoint plan.
interface StepGroup {
  nodeName: string
  steps: Array<ICheckpointSnapshot & { _index: number }>
}

const groupedSteps = computed<StepGroup[]>(() => {
  const groups = new Map<string, StepGroup>()
  props.run.steps.forEach((step, index) => {
    const name = step.nodeName ?? '(unknown node)'
    const existing = groups.get(name)
    const stamped = { ...step, _index: index }
    if (existing) {
      existing.steps.push(stamped)
    } else {
      groups.set(name, { nodeName: name, steps: [stamped] })
    }
  })
  return Array.from(groups.values())
})

const toggleStep = (idx: number): void => {
  expandedSteps.value[idx] = !expandedSteps.value[idx]
}

// Delivery status badge — derived from bridge-side ClassifyStatus(resultCode,
// httpStatus). For passthrough AuditCheckpointNode markers this almost always
// resolves to "success" because the marker node itself cannot fail; meaningful
// values appear when getAuditCheckpoint() is overridden directly on a boundary
// connector (entry/exit), where the bridge captures the actual delivery
// outcome from the connector's response headers.
interface StatusBadge {
  label: string
  classes: string
}

const STATUS_BADGES: Record<AuditResultStatus, StatusBadge> = {
  success: {
    label: 'Delivered',
    classes:
      'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
  },
  failed: {
    label: 'Failed',
    classes: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
  },
  repeat: {
    label: 'Repeating',
    classes:
      'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
  },
  trashed: {
    label: 'Trashed',
    classes: 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
  },
  limit: {
    label: 'Limit',
    classes:
      'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300',
  },
  unknown: {
    label: 'Unknown',
    classes: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
  },
}

const statusBadge = (snap: ICheckpointSnapshot | null): StatusBadge | null => {
  if (!snap || !snap.resultStatus) return null
  return STATUS_BADGES[snap.resultStatus as AuditResultStatus] ?? STATUS_BADGES.unknown
}

const isFailureStatus = (snap: ICheckpointSnapshot | null): boolean => {
  if (!snap?.resultStatus) return false
  return snap.resultStatus === 'failed' || snap.resultStatus === 'repeat' || snap.resultStatus === 'limit'
}
</script>

<template>
  <article class="rounded-lg border border-gray-200 bg-white p-3 shadow-xs dark:border-gray-700 dark:bg-gray-900">
    <header class="mb-2">
      <div class="text-sm font-semibold text-gray-900 dark:text-white">
        {{ run.topologyName || 'Unknown topology' }}
      </div>
      <div class="truncate text-xs text-gray-500 dark:text-gray-400" :title="run.correlationId">
        {{ run.correlationId }}
      </div>
    </header>

    <div class="grid grid-cols-1 gap-2">
      <!-- Entry -->
      <section class="rounded-md border border-gray-100 p-2 dark:border-gray-700">
        <div class="mb-1 flex items-center justify-between gap-2">
          <span class="text-xs font-medium uppercase tracking-wide text-blue-700 dark:text-blue-400">
            Entry
          </span>
          <div class="flex items-center gap-1">
            <span
              v-if="statusBadge(run.entry)"
              :class="['rounded-full px-2 py-0.5 text-xs font-medium', statusBadge(run.entry)!.classes]"
              :title="run.entry?.httpStatus != null ? `HTTP ${run.entry.httpStatus} · code ${run.entry.resultCode}` : undefined"
            >
              {{ statusBadge(run.entry)!.label }}
            </span>
            <span v-if="run.entry?.nodeName" class="truncate rounded-full bg-blue-50 px-2 py-0.5 text-xs text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
              {{ run.entry.nodeName }}
            </span>
          </div>
        </div>
        <div v-if="run.entry" class="space-y-1 text-xs text-gray-700 dark:text-gray-300">
          <div><span class="font-medium">Time:</span> {{ formatTime(run.entry.time) }}</div>
          <div
            v-if="isFailureStatus(run.entry) && run.entry.resultMessage"
            class="rounded border border-red-200 bg-red-50 p-1.5 text-red-800 dark:border-red-700 dark:bg-red-900/30 dark:text-red-200"
          >
            <span class="font-medium">Result:</span> {{ run.entry.resultMessage }}
          </div>
          <template v-if="isMarkerOnly(run.entry)">
            <div class="italic text-gray-500 dark:text-gray-400">
              (marker — bez payloadu)
            </div>
          </template>
          <template v-else>
            <div
              v-if="payloadFlags(run.entry).truncated"
              class="rounded border border-amber-200 bg-amber-50 p-1.5 text-amber-800 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200"
            >
              Payload překročil limit 64 KB
              <span v-if="payloadFlags(run.entry).originalSizeBytes">
                (originalSize: {{ payloadFlags(run.entry).originalSizeBytes }} B)
              </span>;
              zužte allowlist v <code>IAuditCheckpoint.fields</code> nebo přesuňte checkpoint za split.
            </div>
            <button
              type="button"
              class="mt-1 inline-flex items-center gap-1 text-blue-600 hover:underline dark:text-blue-400"
              @click="showEntryPayload = !showEntryPayload"
            >
              <ChevronDown v-if="!showEntryPayload" class="h-3 w-3" />
              <ChevronUp v-else class="h-3 w-3" />
              {{ showEntryPayload ? 'Hide payload' : 'Show payload' }}
            </button>
            <pre
              v-if="showEntryPayload"
              class="mt-1 max-h-64 overflow-auto rounded bg-gray-50 p-2 text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-200"
            >{{ entryPayloadText }}</pre>
          </template>
        </div>
        <div v-else class="text-xs italic text-gray-400 dark:text-gray-500">
          Not captured (no process_entry checkpoint declared on this topology).
        </div>
      </section>

      <!-- Steps (intermediate, optional) -->
      <section
        v-if="run.steps.length > 0"
        class="rounded-md border border-gray-100 p-2 dark:border-gray-700"
      >
        <div class="mb-1 text-xs font-medium uppercase tracking-wide text-purple-700 dark:text-purple-400">
          Steps
        </div>
        <div class="space-y-2">
          <div
            v-for="group in groupedSteps"
            :key="group.nodeName"
            class="rounded border border-gray-100 p-1.5 dark:border-gray-700"
          >
            <div class="mb-1 truncate text-xs font-medium text-gray-800 dark:text-gray-200">
              {{ group.nodeName }}
            </div>
            <div
              v-for="step in group.steps"
              :key="step._index"
              class="space-y-1 border-t border-dashed border-gray-100 pt-1 text-xs text-gray-700 first:border-t-0 first:pt-0 dark:border-gray-700 dark:text-gray-300"
            >
              <div class="flex items-center justify-between gap-2">
                <span><span class="font-medium">Time:</span> {{ formatTime(step.time) }}</span>
                <span
                  v-if="statusBadge(step)"
                  :class="['rounded-full px-2 py-0.5 text-xs font-medium', statusBadge(step)!.classes]"
                  :title="step.httpStatus != null ? `HTTP ${step.httpStatus} · code ${step.resultCode}` : undefined"
                >
                  {{ statusBadge(step)!.label }}
                </span>
              </div>
              <div
                v-if="isFailureStatus(step) && step.resultMessage"
                class="rounded border border-red-200 bg-red-50 p-1.5 text-red-800 dark:border-red-700 dark:bg-red-900/30 dark:text-red-200"
              >
                <span class="font-medium">Result:</span> {{ step.resultMessage }}
              </div>
              <template v-if="isMarkerOnly(step)">
                <div class="italic text-gray-500 dark:text-gray-400">
                  (marker — bez payloadu)
                </div>
              </template>
              <template v-else>
                <div
                  v-if="payloadFlags(step).truncated"
                  class="rounded border border-amber-200 bg-amber-50 p-1.5 text-amber-800 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200"
                >
                  Payload překročil limit 64 KB
                  <span v-if="payloadFlags(step).originalSizeBytes">
                    (originalSize: {{ payloadFlags(step).originalSizeBytes }} B)
                  </span>;
                  zužte allowlist v <code>IAuditCheckpoint.fields</code> nebo přesuňte checkpoint za split.
                </div>
                <button
                  type="button"
                  class="mt-1 inline-flex items-center gap-1 text-purple-600 hover:underline dark:text-purple-400"
                  @click="toggleStep(step._index)"
                >
                  <ChevronDown v-if="!expandedSteps[step._index]" class="h-3 w-3" />
                  <ChevronUp v-else class="h-3 w-3" />
                  {{ expandedSteps[step._index] ? 'Hide payload' : 'Show payload' }}
                </button>
                <pre
                  v-if="expandedSteps[step._index]"
                  class="mt-1 max-h-64 overflow-auto rounded bg-gray-50 p-2 text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-200"
                >{{ formatPayload(step.payload) }}</pre>
              </template>
            </div>
          </div>
        </div>
      </section>

      <!-- Exit -->
      <section class="rounded-md border border-gray-100 p-2 dark:border-gray-700">
        <div class="mb-1 flex items-center justify-between gap-2">
          <span class="text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-400">
            Exit
          </span>
          <div class="flex items-center gap-1">
            <span
              v-if="statusBadge(run.exit)"
              :class="['rounded-full px-2 py-0.5 text-xs font-medium', statusBadge(run.exit)!.classes]"
              :title="run.exit?.httpStatus != null ? `HTTP ${run.exit.httpStatus} · code ${run.exit.resultCode}` : undefined"
            >
              {{ statusBadge(run.exit)!.label }}
            </span>
            <span v-if="run.exit?.nodeName" class="truncate rounded-full bg-emerald-50 px-2 py-0.5 text-xs text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
              {{ run.exit.nodeName }}
            </span>
          </div>
        </div>
        <div v-if="run.exit" class="space-y-1 text-xs text-gray-700 dark:text-gray-300">
          <div><span class="font-medium">Time:</span> {{ formatTime(run.exit.time) }}</div>
          <div
            v-if="isFailureStatus(run.exit) && run.exit.resultMessage"
            class="rounded border border-red-200 bg-red-50 p-1.5 text-red-800 dark:border-red-700 dark:bg-red-900/30 dark:text-red-200"
          >
            <span class="font-medium">Result:</span> {{ run.exit.resultMessage }}
          </div>
          <template v-if="isMarkerOnly(run.exit)">
            <div class="italic text-gray-500 dark:text-gray-400">
              (marker — bez payloadu)
            </div>
          </template>
          <template v-else>
            <div
              v-if="payloadFlags(run.exit).truncated"
              class="rounded border border-amber-200 bg-amber-50 p-1.5 text-amber-800 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200"
            >
              Payload překročil limit 64 KB
              <span v-if="payloadFlags(run.exit).originalSizeBytes">
                (originalSize: {{ payloadFlags(run.exit).originalSizeBytes }} B)
              </span>;
              zužte allowlist v <code>IAuditCheckpoint.fields</code> nebo přesuňte checkpoint za split.
            </div>
            <button
              type="button"
              class="mt-1 inline-flex items-center gap-1 text-emerald-600 hover:underline dark:text-emerald-400"
              @click="showExitPayload = !showExitPayload"
            >
              <ChevronDown v-if="!showExitPayload" class="h-3 w-3" />
              <ChevronUp v-else class="h-3 w-3" />
              {{ showExitPayload ? 'Hide payload' : 'Show payload' }}
            </button>
            <pre
              v-if="showExitPayload"
              class="mt-1 max-h-64 overflow-auto rounded bg-gray-50 p-2 text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-200"
            >{{ exitPayloadText }}</pre>
          </template>
        </div>
        <div v-else class="text-xs italic text-gray-400 dark:text-gray-500">
          Not captured (no process_exit checkpoint declared on this topology).
        </div>
      </section>
    </div>
  </article>
</template>
