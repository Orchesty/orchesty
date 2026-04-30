<script setup lang="ts">
import { ref, computed } from 'vue'
import { Bot } from 'lucide-vue-next'
import type { ChatMessage } from '@/types/trace'
import CopyValue from '@/components/ui/CopyValue.vue'

interface Props {
  message: ChatMessage
}

const props = defineProps<Props>()

const emit = defineEmits<{
  save: [message: ChatMessage]
  copy: [content: string]
  exportPdf: [content: string]
}>()

const isUser = computed(() => props.message.role === 'user')
const isAssistant = computed(() => props.message.role === 'assistant')

// Save state
const saved = ref(false)

// Extract plain text from HTML content
const textContent = computed(() => {
  const tempDiv = document.createElement('div')
  tempDiv.innerHTML = props.message.content
  return tempDiv.textContent || tempDiv.innerText || ''
})

const handleSave = () => {
  emit('save', props.message)
  saved.value = true
  setTimeout(() => {
    saved.value = false
  }, 2000)
}

const handleExportPdf = () => {
  emit('exportPdf', props.message.content)
}
</script>

<template>
  <div class="p-6 bg-white dark:bg-gray-800 shadow-xs rounded-lg flex items-start gap-6 group relative pe-14">
    <!-- Avatar / Icon -->
    <template v-if="isUser">
      <svg class="h-6 w-6 rounded-full text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
        <path d="M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z"/>
      </svg>
    </template>
    
    <template v-if="isAssistant">
      <Bot class="h-7 w-7 shrink-0 text-primary-600 dark:text-primary-500" aria-hidden="true" />
    </template>

    <!-- Message Content -->
    <div class="flex-1 min-w-0">
      <div class="prose prose-sm dark:prose-invert max-w-none prose-p:my-2 prose-headings:my-3" v-html="message.content"></div>
      
      <!-- Action Buttons (only for assistant messages, hidden while the
           typewriter animation is still streaming) -->
      <div v-if="isAssistant && message.canSave && !message.streaming" class="space-x-2 flex items-center mt-3">
        <!-- Save Button -->
        <button 
          type="button" 
          :title="saved ? 'Saved!' : 'Save'"
          @click="handleSave"
          :class="[
            'inline-flex cursor-pointer justify-center rounded-lg p-1.5 transition-colors',
            saved
              ? 'text-green-600 dark:text-green-400'
              : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white'
          ]"
        >
          <svg v-if="!saved" class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 10V4a1 1 0 0 0-1-1H9.914a1 1 0 0 0-.707.293L5.293 7.207A1 1 0 0 0 5 7.914V20a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2M10 3v4a1 1 0 0 1-1 1H5m5 6h9m0 0-2-2m2 2-2 2"/>
          </svg>
          <svg v-else class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor">
            <path d="M382-240 154-468l57-57 171 171 367-367 57 57-424 424Z" />
          </svg>
          <span class="sr-only">{{ saved ? 'Saved!' : 'Save' }}</span>
        </button>
        
        <!-- Copy Button -->
        <CopyValue :value="textContent" :html-value="message.content" hide-value title="Copy message" />
        
        <!-- Export PDF Button -->
        <button 
          type="button"
          title="Export PDF"
          @click="handleExportPdf"
          class="inline-flex cursor-pointer justify-center rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
        >
          <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path fill-rule="evenodd" d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z" clip-rule="evenodd"/>
          </svg>
          <span class="sr-only">Export PDF</span>
        </button>
      </div>
    </div>
  </div>
</template>

