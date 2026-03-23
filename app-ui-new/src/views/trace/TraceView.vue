<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import { Bot } from 'lucide-vue-next'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Button from '@/components/ui/Button.vue'
import ChatMessage from '@/components/trace/ChatMessage.vue'
import ChatInput from '@/components/trace/ChatInput.vue'
import TraceReportsDrawer from '@/components/trace/TraceReportsDrawer.vue'
import TraceReportModal from '@/components/trace/TraceReportModal.vue'
import type { ChatMessage as ChatMessageType, TraceReport } from '@/types/trace'
import { sendChatMessage, fetchReports, saveReport, updateReportTitle, deleteReport } from '@/services/traceService'
import initialChatData from '@/assets/mock-data/trace-initial-chat.json'

// State
const messages = ref<ChatMessageType[]>([])
const reports = ref<TraceReport[]>([])
const sending = ref(false)
const reportsDrawerOpen = ref(false)
const reportModalOpen = ref(false)
const selectedReport = ref<TraceReport | null>(null)
const chatContainerEl = ref<HTMLElement | null>(null)

// Load initial data
onMounted(async () => {
  // Load initial chat messages
  messages.value = initialChatData.map(msg => ({
    ...msg,
    timestamp: new Date(msg.timestamp)
  })) as ChatMessageType[]
  
  // Load saved reports
  await loadReports()
  
  // Scroll to bottom
  await nextTick()
  scrollToBottom()
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

// Send message handler
const handleSendMessage = async (messageText: string) => {
  // Add user message
  const userMessage: ChatMessageType = {
    id: `msg-user-${Date.now()}`,
    role: 'user',
    content: messageText.replace(/\n/g, '<br>'),
    timestamp: new Date(),
    status: 'sent'
  }
  messages.value.push(userMessage)
  
  await nextTick()
  scrollToBottom()
  
  // Send to backend (mock)
  sending.value = true
  try {
    const assistantMessage = await sendChatMessage(messageText)
    messages.value.push(assistantMessage)
    
    await nextTick()
    scrollToBottom()
  } catch (error) {
    console.error('Failed to send message:', error)
  } finally {
    sending.value = false
  }
}

// Save report handler
const handleSaveReport = async (message: ChatMessageType) => {
  try {
    // Generate title from first 50 characters of message content
    const tempDiv = document.createElement('div')
    tempDiv.innerHTML = message.content
    const textContent = tempDiv.textContent || tempDiv.innerText || ''
    const title = textContent.substring(0, 50).trim() + (textContent.length > 50 ? '...' : '')
    
    const newReport = await saveReport({
      title,
      content: message.content,
      timestamp: message.timestamp,
      messageId: message.id
    })
    
    reports.value.unshift(newReport)
    
  } catch (error) {
    console.error('Failed to save report:', error)
  }
}

// Open report modal
const handleOpenReport = (report: TraceReport) => {
  selectedReport.value = report
  reportModalOpen.value = true
  reportsDrawerOpen.value = false
}

// Rename report
const handleRenameReport = async (id: string, newTitle: string) => {
  try {
    await updateReportTitle(id, newTitle)
    
    const report = reports.value.find(r => r.id === id)
    if (report) {
      report.title = newTitle
    }
    
  } catch (error) {
    console.error('Failed to rename report:', error)
  }
}

// Delete report
const handleDeleteReport = async (id: string) => {
  try {
    await deleteReport(id)
    reports.value = reports.value.filter(r => r.id !== id)
    
    // Close modal if this report is open
    if (selectedReport.value?.id === id) {
      reportModalOpen.value = false
      selectedReport.value = null
    }
    
  } catch (error) {
    console.error('Failed to delete report:', error)
  }
}

// Delete from modal
const handleDeleteFromModal = async (id: string) => {
  await handleDeleteReport(id)
  reportModalOpen.value = false
}

const handleCopy = () => {
  // clipboard handled by ChatMessage component
}

const handleExportPdf = () => {
  // TODO: implement PDF export
}
</script>

<template>
  <DashboardLayout>
    <div class="relative h-[calc(100vh-8rem)] w-full bg-gray-50 dark:bg-gray-900">
      <!-- Reports Button (absolute, top-right) -->
      <div class="absolute top-4 right-4 z-10">
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
          <ChatMessage 
            v-for="msg in messages" 
            :key="msg.id"
            :message="msg"
            @save="handleSaveReport"
            @copy="handleCopy"
            @export-pdf="handleExportPdf"
          />
          
          <!-- Loading Indicator -->
          <div v-if="sending" class="p-6 bg-white dark:bg-gray-800 shadow-sm rounded-lg flex items-start gap-6">
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
  </DashboardLayout>
</template>

