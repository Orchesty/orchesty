<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { Bot } from 'lucide-vue-next'
import Button from '@/components/ui/Button.vue'
import ChatMessage from '@/components/trace/ChatMessage.vue'
import ChatInput from '@/components/trace/ChatInput.vue'
import TraceReportsDrawer from '@/components/trace/TraceReportsDrawer.vue'
import TraceReportModal from '@/components/trace/TraceReportModal.vue'
import {
  renderAuditReportHtml,
  makeReportId,
  escapeHtml,
} from '@/components/trace/auditReportRenderer'
import { formatAssistantText } from '@/utils/assistantTextFormat'
import { printReport } from '@/components/trace/printReport'
import type { ChatMessage as ChatMessageType, EntityHistoryResponse, TraceReport } from '@/types/trace'
import { fetchReports, saveReport, updateReportTitle, deleteReport } from '@/services/traceService'
import { useTraceSocket } from '@/composables/useTraceSocket'
import { useAuthStore } from '@/stores/auth'
import { useTraceStore } from '@/stores/trace'

// Chat history is hoisted into a Pinia store backed by localStorage so it
// survives route navigation and full reloads. The store owns trimming /
// quota handling.
const traceStore = useTraceStore()
const { messages } = storeToRefs(traceStore)

const reports = ref<TraceReport[]>([])
const sending = ref(false)
const reportsDrawerOpen = ref(false)
const reportModalOpen = ref(false)
const selectedReport = ref<TraceReport | null>(null)
const chatContainerEl = ref<HTMLElement | null>(null)

const authStore = useAuthStore()
const socket = useTraceSocket()
const route = useRoute()
const router = useRouter()

const connectionLabel = computed(() => {
  switch (socket.status.value) {
    case 'open':
      return 'Connected'
    case 'connecting':
      return 'Connecting…'
    case 'reconnecting':
      return 'Reconnecting…'
    case 'closed':
      return 'Disconnected'
    case 'error':
      return 'Connection error'
    default:
      return 'Idle'
  }
})

const connectionBadgeClass = computed(() => {
  switch (socket.status.value) {
    case 'open':
      return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
    case 'connecting':
    case 'reconnecting':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'
    case 'error':
      return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
  }
})

// Typewriter streaming. The Trace backend currently returns the full
// assistant text in a single WebSocket frame; we animate the rendering on the
// client to match the "live" feel users expect from LLM chats. Tunables are
// kept here so the cadence is easy to adjust without spelunking through the
// helper.
// ~30 words per second: one word every ~33 ms reads naturally without the
// chunky feel you get from larger batches per tick.
const STREAM_TICK_MS = 33
const STREAM_WORDS_PER_TICK = 1
let streamCleanup: (() => void) | null = null

// Splits raw text into atoms of "non-space + trailing space(s)" so the
// animation grows on word boundaries while preserving the original
// whitespace (including newlines) needed by formatAssistantContent.
const splitIntoStreamAtoms = (raw: string): string[] => {
  const atoms = raw.match(/\S+\s*|\s+/g)
  return atoms ?? []
}

const cancelActiveStream = () => {
  if (streamCleanup) {
    streamCleanup()
    streamCleanup = null
  }
}

// Drives the typewriter animation for a single assistant message. Renders an
// initial empty placeholder, then progressively appends N atoms per tick and
// re-runs the full formatter (escape -> linkify -> paragraphs) over the
// growing prefix so partial URLs / tags never leak into v-html.
const startStreaming = (id: string, raw: string) => {
  cancelActiveStream()

  const atoms = splitIntoStreamAtoms(raw)
  if (atoms.length === 0) {
    traceStore.updateMessage(id, {
      content: formatAssistantContent(raw),
      rawContent: raw,
      streaming: false,
      canSave: true,
    })
    return
  }

  let cursor = 0
  const finalize = () => {
    traceStore.updateMessage(id, {
      content: formatAssistantContent(raw),
      rawContent: raw,
      streaming: false,
      canSave: true,
    })
    void nextTick().then(scrollToBottom)
  }

  const handle = window.setInterval(() => {
    cursor = Math.min(atoms.length, cursor + STREAM_WORDS_PER_TICK)
    const prefix = atoms.slice(0, cursor).join('')
    traceStore.updateMessage(id, {
      content: formatAssistantContent(prefix),
      rawContent: prefix,
      streaming: cursor < atoms.length,
    })
    void nextTick().then(scrollToBottom)
    if (cursor >= atoms.length) {
      window.clearInterval(handle)
      streamCleanup = null
      finalize()
    }
  }, STREAM_TICK_MS)

  streamCleanup = () => {
    window.clearInterval(handle)
    finalize()
  }
}

// Lifecycle
onMounted(async () => {
  socket.onResponse((data) => {
    sending.value = false

    // Structured audit reports (entity history HTML with tables / cards) read
    // poorly when streamed character-by-character — the layout flickers as
    // partial markup gets re-parsed. Detect them via the same parser the
    // formatter uses and render in one shot.
    const isStructured = tryParseEntityHistory(data.content) !== null

    if (isStructured) {
      cancelActiveStream()
      traceStore.addMessage({
        id: `msg-asst-${Date.now()}`,
        role: 'assistant',
        content: formatAssistantContent(data.content),
        rawContent: data.content,
        timestamp: new Date(),
        status: 'sent',
        canSave: true,
      })
      void nextTick().then(scrollToBottom)
      return
    }

    const id = `msg-asst-${Date.now()}`
    traceStore.addMessage({
      id,
      role: 'assistant',
      content: '<p></p>',
      timestamp: new Date(),
      status: 'sent',
      canSave: false,
      streaming: true,
    })
    void nextTick().then(scrollToBottom)
    startStreaming(id, data.content)
  })

  socket.onError((data) => {
    sending.value = false
    cancelActiveStream()
    traceStore.addMessage({
      id: `msg-err-${Date.now()}`,
      role: 'assistant',
      content: `<p class="text-red-600 dark:text-red-400"><strong>Error ${data.code}:</strong> ${escapeHtml(data.message)}</p>`,
      timestamp: new Date(),
      status: 'error',
    })
    void nextTick().then(scrollToBottom)
  })

  socket.onQuotaExceeded((data) => {
    sending.value = false
    cancelActiveStream()
    traceStore.addMessage({
      id: `msg-quota-${Date.now()}`,
      role: 'assistant',
      content: renderQuotaExceededHtml(data),
      timestamp: new Date(),
      status: 'error',
    })
    void nextTick().then(scrollToBottom)
  })

  const userID = authStore.user?.id
  if (userID) {
    socket.connect(userID)
  }

  await loadReports()
  await nextTick()
  scrollToBottom()

  // Optional `?prompt=start+onboarding` deep-link from external surfaces
  // (Cloud overview banner, marketing site, ...). Sends the prompt as if
  // the user typed it themselves and strips the param from the URL so a
  // page reload doesn't re-fire the same message.
  const prefill = typeof route.query.prompt === 'string' ? route.query.prompt.trim() : ''
  if (prefill !== '') {
    void nextTick().then(() => {
      handleSendMessage(prefill)
      // Strip the query param without unmounting the view (same name,
      // params kept, query.prompt removed).
      const { prompt: _omit, ...rest } = route.query
      void router.replace({ name: route.name as string, params: route.params, query: rest })
    })
  }
})

onUnmounted(() => {
  cancelActiveStream()
  socket.disconnect()
})

const loadReports = async () => {
  try {
    reports.value = await fetchReports()
  } catch (error) {
    console.error('Failed to load reports:', error)
  }
}

const scrollToBottom = () => {
  if (chatContainerEl.value) {
    chatContainerEl.value.scrollTop = chatContainerEl.value.scrollHeight
  }
}

// Tries to extract a per-entity history payload from the assistant text.
// Accepts either pure JSON or JSON wrapped in code fences / surrounding prose
// (the AI sometimes returns the raw MCP result interleaved with commentary).
const tryParseEntityHistory = (raw: string): EntityHistoryResponse | null => {
  const candidates: string[] = [raw]
  const fenceMatch = raw.match(/```(?:json)?\s*([\s\S]+?)\s*```/)
  if (fenceMatch && fenceMatch[1]) candidates.unshift(fenceMatch[1])
  const objectMatch = raw.match(/\{[\s\S]*"runs"\s*:\s*\[[\s\S]*?\][\s\S]*\}/)
  if (objectMatch) candidates.unshift(objectMatch[0])

  for (const candidate of candidates) {
    try {
      const parsed = JSON.parse(candidate)
      if (
        parsed && typeof parsed === 'object'
        && typeof parsed.entity === 'string'
        && Array.isArray(parsed.runs)
      ) {
        return parsed as EntityHistoryResponse
      }
    } catch {
      // try next candidate
    }
  }
  return null
}

// Strip the leading "[onboarding-stage:..]" marker from the raw text before
// HTML formatting. The marker is stage-memory metadata and must never reach
// the user's eyes — ChatMessage independently parses rawContent and forwards
// the same marker to the trace store.
//
// We greedily consume everything on the marker line after the closing `]`
// so a stray "next=foo" outside the bracket (LLM drift from the canonical
// "next= belongs INSIDE the bracket" rule) doesn't leak into the rendered
// body. The bounded `[^\n]*` makes sure we never eat past the line break.
const STAGE_MARKER_LINE_RE = /^\s*\[onboarding-stage:[^\s,\]]+(?:[\s,]+next=[^\s\]]+)?\][^\n]*\n?/

// Wrap raw assistant text into safe HTML so newlines render as paragraphs
// and any URLs become clickable links. If the text contains a per-entity
// history payload, render the structured audit-report template (shared with
// the saved-report modal and PDF view).
//
// Onboarding action blocks ([shell] / [prompt] / [link]) are NOT stripped
// here — they survive into `message.content` as plain text. ChatMessage
// re-parses `rawContent` via parseAssistantBody and replaces them with
// action cards; this HTML is the legacy fallback for messages without
// rawContent.
const formatAssistantContent = (raw: string): string => {
  const history = tryParseEntityHistory(raw)
  if (history) {
    return renderAuditReportHtml(history, {
      generatedAt: new Date(),
      reportId: makeReportId(),
    })
  }

  const cleaned = raw.replace(STAGE_MARKER_LINE_RE, '')
  return formatAssistantText(cleaned)
}

// Send message handler
const handleSendMessage = async (messageText: string) => {
  const trimmed = messageText.trim()
  if (!trimmed) return

  traceStore.addMessage({
    id: `msg-user-${Date.now()}`,
    role: 'user',
    content: escapeHtml(trimmed).replace(/\n/g, '<br>'),
    timestamp: new Date(),
    status: 'sent',
  })

  await nextTick()
  scrollToBottom()

  sending.value = true
  socket.send(trimmed, traceStore.getExtraContext())

  // The "Thinking…" card is rendered conditionally on `sending`. Without an
  // extra scroll pass it lands below the absolutely positioned ChatInput and
  // looks like nothing is happening.
  await nextTick()
  scrollToBottom()
}

// Save report handler
const handleSaveReport = async (message: ChatMessageType) => {
  try {
    const tempDiv = document.createElement('div')
    tempDiv.innerHTML = message.content
    const textContent = tempDiv.textContent || tempDiv.innerText || ''
    const title = textContent.substring(0, 50).trim() + (textContent.length > 50 ? '...' : '')

    const newReport = await saveReport({
      title,
      content: message.content,
      timestamp: message.timestamp,
      messageId: message.id,
    })

    reports.value.unshift(newReport)
  } catch (error) {
    console.error('Failed to save report:', error)
  }
}

const handleOpenReport = (report: TraceReport) => {
  selectedReport.value = report
  reportModalOpen.value = true
  reportsDrawerOpen.value = false
}

const handleRenameReport = async (id: string, newTitle: string) => {
  try {
    await updateReportTitle(id, newTitle)
    const report = reports.value.find((r) => r.id === id)
    if (report) {
      report.title = newTitle
    }
  } catch (error) {
    console.error('Failed to rename report:', error)
  }
}

const handleDeleteReport = async (id: string) => {
  try {
    await deleteReport(id)
    reports.value = reports.value.filter((r) => r.id !== id)
    if (selectedReport.value?.id === id) {
      reportModalOpen.value = false
      selectedReport.value = null
    }
  } catch (error) {
    console.error('Failed to delete report:', error)
  }
}

const handleDeleteFromModal = async (id: string) => {
  await handleDeleteReport(id)
  reportModalOpen.value = false
}

const handleCopy = () => {
  // clipboard handled by ChatMessage component
}

// Both ChatMessage and TraceReportModal emit `exportPdf` with the rendered
// HTML content. We open it in a fresh tab and trigger window.print(); the
// user picks "Save as PDF" in the print dialog.
const handleExportPdf = (content: string) => {
  printReport(content, 'Trace Audit Report')
}

// Render the dedicated `quota_exceeded` info card. The backend (Go) already
// translated PHP's `QuotaExceededException::toPayload()` into a structured
// frame; we just turn the counters into copy and provide the two CTAs the
// user actually needs ("Connect your own LLM" -> Settings/TraceTab,
// "Upgrade plan" -> Cloud overview). Settings + Cloud links are wrapped as
// in-app routes when running inside the same app, otherwise plain anchors.
const renderQuotaExceededHtml = (data: { limit: number; used: number; resetAt: string }): string => {
  const limit = data.limit > 0 ? data.limit : 100
  const used = data.used > 0 ? data.used : limit
  let resetCopy = 'tomorrow at 00:00 UTC'
  if (data.resetAt) {
    const parsed = new Date(data.resetAt)
    if (!Number.isNaN(parsed.valueOf())) {
      resetCopy = parsed.toLocaleString(undefined, {
        weekday: 'short',
        hour: '2-digit',
        minute: '2-digit',
      }) + ' your time'
    }
  }

  return `
    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-900 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200">
      <p class="font-semibold">Daily Trace limit reached</p>
      <p class="mt-1 text-sm">
        You've used <strong>${escapeHtml(String(used))} / ${escapeHtml(String(limit))}</strong>
        Trace messages today on the included Orchesty default LLM. The counter resets ${escapeHtml(resetCopy)}.
      </p>
      <div class="mt-3 flex flex-wrap gap-2 text-sm">
        <a href="/settings/trace" class="inline-flex items-center rounded-md border border-amber-300 bg-white px-3 py-1.5 font-medium text-amber-900 hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100 dark:hover:bg-amber-900">
          Connect your own LLM
        </a>
        <a href="/cloud/overview" class="inline-flex items-center rounded-md px-3 py-1.5 font-medium text-amber-900 underline-offset-2 hover:underline dark:text-amber-100">
          Upgrade plan
        </a>
      </div>
    </div>
  `
}
</script>

<template>
  <main class="h-full overflow-y-auto"><div class="px-4 pb-4 pt-6">
    <div class="relative flex h-[calc(100vh-8rem)] w-full bg-gray-50 dark:bg-gray-900">
      <div class="relative flex-1">
      <!-- Header (absolute, top): connection status + reports button -->
      <div class="absolute top-4 right-4 z-10 flex items-center gap-3">
        <span
          :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', connectionBadgeClass]"
          :title="`Trace socket: ${socket.status.value}`"
        >
          <span class="me-1.5 h-1.5 w-1.5 rounded-full bg-current"></span>
          {{ connectionLabel }}
        </span>
        <Button variant="outline" @click="reportsDrawerOpen = true">
          <svg class="w-5 h-5 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2h-7Z" clip-rule="evenodd"/>
          </svg>
          Reports
        </Button>
      </div>

      <!-- Chat Messages (scrollable) -->
      <div
        ref="chatContainerEl"
        class="overflow-y-auto h-[calc(100%-6rem)]"
      >
        <div class="max-w-4xl mx-auto py-4 space-y-6 px-4 pt-16 pb-4">
          <!-- Empty state -->
          <div v-if="!messages.length && !sending" class="flex flex-col items-center justify-center text-center py-16 text-gray-500 dark:text-gray-400">
            <Bot class="h-12 w-12 mb-4 text-primary-600 dark:text-primary-500" aria-hidden="true" />
            <p class="text-lg font-medium text-gray-700 dark:text-gray-300">Ask Trace about an audited entity</p>
            <p class="mt-1 text-sm">For example: <em>"Find product with SKU XYZ"</em></p>
            <button
              type="button"
              @click="handleSendMessage('start onboarding')"
              :disabled="sending"
              class="mt-6 inline-flex items-center gap-2 rounded-full border border-primary-200 bg-primary-50 px-4 py-1.5 text-sm font-medium text-primary-700 transition-colors hover:bg-primary-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-primary-900 dark:bg-primary-950 dark:text-primary-300 dark:hover:bg-primary-900"
            >
              <svg class="h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 5l7 7-7 7M5 12h14" />
              </svg>
              Start onboarding
            </button>
          </div>

          <ChatMessage
            v-for="msg in messages"
            :key="msg.id"
            :message="msg"
            @save="handleSaveReport"
            @copy="handleCopy"
            @export-pdf="handleExportPdf"
          />

          <!-- Loading Indicator -->
          <div v-if="sending" class="p-6 bg-white dark:bg-gray-800 shadow-xs rounded-lg flex items-start gap-6">
            <Bot class="h-7 w-7 shrink-0 text-primary-600 dark:text-primary-500" aria-hidden="true" />
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <div class="flex space-x-1">
                  <div class="w-2 h-2 bg-gray-400 dark:bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                  <div class="w-2 h-2 bg-gray-400 dark:bg-gray-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                  <div class="w-2 h-2 bg-gray-400 dark:bg-gray-500 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                </div>
                <span class="text-sm text-gray-500 dark:text-gray-400">Thinking...</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Chat Input (fixed bottom) -->
      <ChatInput @send="handleSendMessage" :loading="sending" />
      </div>
    </div>

    <!-- Drawers & Modals -->
    <TraceReportsDrawer
      v-model="reportsDrawerOpen"
      :reports="reports"
      @open-report="handleOpenReport"
      @rename="handleRenameReport"
      @delete="handleDeleteReport"
    />

    <TraceReportModal
      v-model="reportModalOpen"
      :report="selectedReport"
      @delete="handleDeleteFromModal"
      @copy="handleCopy"
      @export-pdf="handleExportPdf"
    />
  </div></main>
</template>
