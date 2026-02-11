<script setup lang="ts">
import { ref, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { Bot } from 'lucide-vue-next'
import ChatMessage from './ChatMessage.vue'
import type { ChatMessage as ChatMessageType } from '@/types/trace'
import { sendChatMessage } from '@/services/traceService'
import initialChatData from '@/assets/mock-data/trace-initial-chat.json'

interface Props {
  modelValue: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'save': [message: ChatMessageType]
}>()

// State
const messages = ref<ChatMessageType[]>([])
const messageInput = ref('')
const sending = ref(false)
const chatContainerEl = ref<HTMLElement | null>(null)
const textareaRef = ref<HTMLTextAreaElement | null>(null)

const autoResize = () => {
  const el = textareaRef.value
  if (!el) return
  el.style.height = 'auto'
  el.style.height = el.scrollHeight + 'px'
}
// eslint-disable-next-line @typescript-eslint/no-explicit-any
const drawerInstance = ref<any>(null)

// Load initial data
onMounted(async () => {
  // Load initial chat messages
  messages.value = initialChatData.map(msg => ({
    ...msg,
    timestamp: new Date(msg.timestamp)
  })) as ChatMessageType[]
  
  // Initialize Flowbite Drawer
  await nextTick()
  const drawerElement = document.getElementById('traceDrawer')
  
  if (drawerElement) {
    const { Drawer } = await import('flowbite')
    
    drawerInstance.value = new Drawer(drawerElement, {
      placement: 'right',
      backdrop: false, // NO OVERLAY
      bodyScrolling: true,
      edge: false,
      edgeOffset: '',
      onHide: () => {
        emit('update:modelValue', false)
      },
    })
  }
})

// Watch for modelValue changes
watch(
  () => props.modelValue,
  async (newValue) => {
    await nextTick()
    
    if (drawerInstance.value) {
      if (newValue) {
        drawerInstance.value.show()
      } else {
        drawerInstance.value.hide()
      }
    }
  },
)

// Cleanup on unmount
onBeforeUnmount(() => {
  if (drawerInstance.value) {
    drawerInstance.value.hide()
  }
})

const scrollToBottom = () => {
  if (chatContainerEl.value) {
    chatContainerEl.value.scrollTop = chatContainerEl.value.scrollHeight
  }
}

// Send message handler
const handleSendMessage = async () => {
  const trimmedMessage = messageInput.value.trim()
  if (!trimmedMessage || sending.value) return
  
  // Add user message
  const userMessage: ChatMessageType = {
    id: `msg-user-${Date.now()}`,
    role: 'user',
    content: trimmedMessage.replace(/\n/g, '<br>'),
    timestamp: new Date(),
    status: 'sent'
  }
  messages.value.push(userMessage)
  messageInput.value = ''
  if (textareaRef.value) {
    textareaRef.value.style.height = 'auto'
  }
  
  await nextTick()
  scrollToBottom()
  
  // Send to backend (mock)
  sending.value = true
  try {
    const assistantMessage = await sendChatMessage(trimmedMessage)
    messages.value.push(assistantMessage)
    
    await nextTick()
    scrollToBottom()
  } catch (error) {
    console.error('Failed to send message:', error)
  } finally {
    sending.value = false
  }
}

const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    handleSendMessage()
  }
}

const handleSaveReport = (message: ChatMessageType) => {
  emit('save', message)
}

const handleCopy = () => {
  console.log('Content copied to clipboard')
}

const handleExportPdf = () => {
  console.log('Export to PDF - functionality to be implemented')
}

const handleClose = () => {
  emit('update:modelValue', false)
}
</script>

<template>
  <div 
    id="traceDrawer" 
    class="fixed top-[53px] right-0 z-40 h-[calc(100vh-53px)] w-96 transition-transform translate-x-full bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700" 
    tabindex="-1" 
    aria-labelledby="traceDrawerLabel" 
    aria-hidden="true"
  >
    <div class="flex flex-col h-full">
      <!-- Drawer Header with Close Button -->
      <div class="relative p-4 border-b border-gray-200 dark:border-gray-700 shrink-0">
        <h5 id="traceDrawerLabel" class="inline-flex items-center text-sm font-semibold uppercase text-gray-500 dark:text-gray-400">
          Trace
        </h5>
        <button
          type="button"
          @click="handleClose"
          class="absolute right-2.5 top-2.5 inline-flex items-center rounded-lg bg-transparent p-1.5 text-sm text-gray-400 hover:bg-gray-200 hover:text-gray-900 dark:hover:bg-gray-600 dark:hover:text-white"
        >
          <svg aria-hidden="true" class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
            <path
              fill-rule="evenodd"
              d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
              clip-rule="evenodd"
            ></path>
          </svg>
          <span class="sr-only">Close menu</span>
        </button>
      </div>

      <!-- Scrollable Chat Content -->
      <div 
        ref="chatContainerEl"
        class="flex-1 overflow-y-auto p-4 space-y-4 min-h-0"
      >
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

      <!-- Input Field at Bottom -->
      <div class="p-4 border-t border-gray-200 dark:border-gray-700 shrink-0">
        <form @submit.prevent="handleSendMessage">
          <div class="flex items-center gap-2 border border-gray-300 rounded-lg bg-white px-3 py-2 dark:bg-gray-800 dark:border-gray-600">
            <label for="trace-drawer-input" class="sr-only">Write message</label>
            <textarea 
              ref="textareaRef"
              v-model="messageInput"
              @keydown="handleKeydown"
              @input="autoResize"
              :disabled="sending"
              placeholder="Write a prompt..." 
              id="trace-drawer-input" 
              rows="1"
              class="block flex-1 border-0 bg-transparent px-0 text-sm text-gray-800 focus:ring-0 resize-none overflow-y-auto max-h-[20vh] dark:text-white dark:placeholder:text-gray-400"
            ></textarea>
            <button 
              type="submit" 
              :disabled="!messageInput.trim() || sending"
              class="inline-flex cursor-pointer justify-center rounded-full p-2 text-primary-600 hover:bg-primary-100 dark:text-primary-500 dark:hover:bg-gray-600 flex-shrink-0 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg 
                v-if="!sending"
                class="h-4 w-4 rotate-90 rtl:-rotate-90" 
                aria-hidden="true" 
                xmlns="http://www.w3.org/2000/svg" 
                fill="currentColor" 
                viewBox="0 0 18 20"
              >
                <path d="m17.914 18.594-8-18a1 1 0 0 0-1.828 0l-8 18a1 1 0 0 0 1.157 1.376L8 18.281V9a1 1 0 0 1 2 0v9.281l6.758 1.689a1 1 0 0 0 1.156-1.376Z" />
              </svg>
              <svg 
                v-else
                aria-hidden="true" 
                role="status" 
                class="h-4 w-4 animate-spin" 
                viewBox="0 0 100 101" 
                fill="none" 
                xmlns="http://www.w3.org/2000/svg"
              >
                <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentColor"/>
              </svg>
              <span class="sr-only">Send message</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
:deep(.p-6) {
  padding: 0;
  padding-top: 1.5rem;
  padding-bottom: 1.5rem;
}

:deep(.pe-14) {
  padding-inline-end: 0;
}
</style>
