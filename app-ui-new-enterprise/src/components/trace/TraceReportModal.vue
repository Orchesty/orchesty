<script setup lang="ts">
import { computed } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import { useCopyToClipboard } from '@/composables/useCopyToClipboard'
import type { TraceReport } from '@/types/trace'

interface Props {
  modelValue: boolean
  report: TraceReport | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'delete': [id: string]
  'copy': [content: string]
  'exportPdf': [content: string]
}>()

// Copy functionality
const { copied, copyToClipboard } = useCopyToClipboard()

// Extract plain text from HTML content
const textContent = computed(() => {
  if (!props.report) return ''
  const tempDiv = document.createElement('div')
  tempDiv.innerHTML = props.report.content
  return tempDiv.textContent || tempDiv.innerText || ''
})

const handleCopy = async () => {
  if (props.report) {
    await copyToClipboard(textContent.value)
    emit('copy', textContent.value)
  }
}

const handleDelete = () => {
  if (props.report) {
    emit('delete', props.report.id)
  }
}

const handleExportPdf = () => {
  if (props.report) {
    emit('exportPdf', props.report.content)
  }
}
</script>

<template>
  <Modal
    id="trace-report-modal"
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    :title="report?.title || 'Report'"
    size="4xl"
  >
    <!-- Report Content. The stored HTML is the styled audit report
         (auditReportRenderer output) — its root element carries both
         light and dark Tailwind variants, so we just opt out of prose
         styling here and let the report render itself. -->
    <div
      v-if="report"
      class="not-prose rounded-lg overflow-hidden"
      v-html="report.content"
    ></div>
    
    <!-- Footer Actions -->
    <template #footer-actions>
      <div class="flex items-center justify-between w-full">
        <div class="flex items-center gap-2">
          <!-- Export PDF Button -->
          <Button variant="outline" @click="handleExportPdf">
            <svg class="w-5 h-5 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2 2 2 0 0 0 2 2h12a2 2 0 0 0 2-2 2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2h-7Zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5H5Zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1Zm4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11H11Zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626H12Zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2h-2Z" clip-rule="evenodd"/>
            </svg>
            Export PDF
          </Button>
          
          <!-- Copy Button -->
          <Button 
            variant="outline" 
            @click="handleCopy"
            :class="copied ? 'text-green-600 dark:text-green-400' : ''"
          >
            <svg 
              v-if="!copied"
              class="w-5 h-5 me-1.5" 
              aria-hidden="true" 
              xmlns="http://www.w3.org/2000/svg" 
              height="24px" 
              viewBox="0 -960 960 960" 
              width="24px" 
              fill="currentColor"
            >
              <path d="M360-240q-33 0-56.5-23.5T280-320v-480q0-33 23.5-56.5T360-880h360q33 0 56.5 23.5T800-800v480q0 33-23.5 56.5T720-240H360Zm0-80h360v-480H360v480ZM200-80q-33 0-56.5-23.5T120-160v-560h80v560h440v80H200Zm160-240v-480 480Z"/>
            </svg>
            <svg 
              v-else
              class="w-5 h-5 me-1.5" 
              aria-hidden="true" 
              xmlns="http://www.w3.org/2000/svg" 
              height="24px" 
              viewBox="0 -960 960 960" 
              width="24px" 
              fill="currentColor"
            >
              <path d="M382-240 154-468l57-57 171 171 367-367 57 57-424 424Z"/>
            </svg>
            {{ copied ? 'Copied!' : 'Copy' }}
          </Button>
          
          <!-- Delete Button -->
          <Button variant="danger" @click="handleDelete">
            <svg class="w-5 h-5 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M8.586 2.586A2 2 0 0 1 10 2h4a2 2 0 0 1 2 2v2h3a1 1 0 1 1 0 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V8a1 1 0 0 1 0-2h3V4a2 2 0 0 1 .586-1.414ZM10 6h4V4h-4v2Zm1 4a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Zm4 0a1 1 0 1 0-2 0v8a1 1 0 1 0 2 0v-8Z" clip-rule="evenodd"/>
            </svg>
            Delete
          </Button>
        </div>
        
        <!-- Close Button -->
        <Button variant="outline" @click="$emit('update:modelValue', false)">
          Close
        </Button>
      </div>
    </template>
  </Modal>
</template>

