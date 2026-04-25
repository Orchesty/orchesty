<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
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
import { printReport } from '@/components/trace/printReport'
import type { ChatMessage as ChatMessageType, EntityHistoryResponse, TraceReport } from '@/types/trace'
import { fetchReports, saveReport, updateReportTitle, deleteReport } from '@/services/traceService'
import { useTraceSocket } from '@/composables/useTraceSocket'
import { useAuthStore } from '@/stores/auth'

// State
const messages = ref<ChatMessageType[]>([])
const reports = ref<TraceReport[]>([])
const sending = ref(false)
const reportsDrawerOpen = ref(false)
const reportModalOpen = ref(false)
const selectedReport = ref<TraceReport | null>(null)
const chatContainerEl = ref<HTMLElement | null>(null)

const authStore = useAuthStore()
const socket = useTraceSocket()

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

// Lifecycle
onMounted(async () => {
  socket.onResponse((data) => {
    sending.value = false
    messages.value.push({
      id: `msg-asst-${Date.now()}`,
      role: 'assistant',
      content: formatAssistantContent(data.content),
      timestamp: new Date(),
      status: 'sent',
      canSave: true,
    })
    void nextTick().then(scrollToBottom)
  })

  socket.onError((data) => {
    sending.value = false
    messages.value.push({
      id: `msg-err-${Date.now()}`,
      role: 'assistant',
      content: `<p class="text-red-600 dark:text-red-400"><strong>Error ${data.code}:</strong> ${escapeHtml(data.message)}</p>`,
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
})

onUnmounted(() => {
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

// Wrap raw assistant text into safe HTML so newlines render as paragraphs.
// If the text contains a per-entity history payload, render the structured
// audit-report template (shared with the saved-report modal and PDF view).
const formatAssistantContent = (raw: string): string => {
  const history = tryParseEntityHistory(raw)
  if (history) {
    return renderAuditReportHtml(history, {
      generatedAt: new Date(),
      reportId: makeReportId(),
    })
  }

  const escaped = escapeHtml(raw)
  const paragraphs = escaped
    .split(/\n{2,}/)
    .map((p) => `<p>${p.replace(/\n/g, '<br>')}</p>`)
    .join('')
  return paragraphs || '<p></p>'
}

// Send message handler
const handleSendMessage = async (messageText: string) => {
  const trimmed = messageText.trim()
  if (!trimmed) return

  messages.value.push({
    id: `msg-user-${Date.now()}`,
    role: 'user',
    content: escapeHtml(trimmed).replace(/\n/g, '<br>'),
    timestamp: new Date(),
    status: 'sent',
  })

  await nextTick()
  scrollToBottom()

  sending.value = true
  socket.send(trimmed)
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
