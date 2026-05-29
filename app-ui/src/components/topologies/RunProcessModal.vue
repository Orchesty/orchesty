<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import Textarea from '@/components/ui/datagrid/Textarea.vue'

interface Props {
  modelValue: boolean
  nodeName?: string
  nodeId?: string
  hasBreakpointMessages?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  hasBreakpointMessages: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  run: [jsonData: string]
}>()

const jsonInput = ref('{\n  \n}')
const validationError = ref<string | null>(null)
const isRunning = ref(false)
const showBreakpointAlert = ref(false)

watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue) {
      jsonInput.value = '{\n  \n}'
      validationError.value = null
      isRunning.value = false
      showBreakpointAlert.value = props.hasBreakpointMessages
    }
  }
)

// Real-time JSON validation
watch(jsonInput, (newValue) => {
  if (!newValue || newValue.trim() === '') {
    validationError.value = null
    return
  }

  try {
    JSON.parse(newValue)
    validationError.value = null
  } catch (error) {
    validationError.value = error instanceof Error ? error.message : 'Invalid JSON format'
  }
})

const isValid = computed(() => {
  if (!jsonInput.value || jsonInput.value.trim() === '') return false
  return validationError.value === null
})

const modalTitle = computed(() => {
  return props.nodeName ? `Run Process - ${props.nodeName}` : 'Run Process'
})

const handleRun = () => {
  if (!isValid.value) return

  isRunning.value = true
  emit('run', jsonInput.value)
  
  // Close modal after a short delay
  setTimeout(() => {
    emit('update:modelValue', false)
    isRunning.value = false
  }, 500)
}

const handleClose = () => {
  emit('update:modelValue', false)
}
</script>

<template>
  <Modal
    id="run-process-modal"
    :model-value="modelValue"
    :title="modalTitle"
    size="lg"
    @update:model-value="handleClose"
  >
    <div class="space-y-4">
      <div
        v-if="showBreakpointAlert"
        class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-900/20"
      >
        <svg
          class="mt-0.5 h-5 w-5 shrink-0 text-amber-500"
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
        >
          <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
          <line x1="12" y1="9" x2="12" y2="13" />
          <line x1="12" y1="17" x2="12.01" y2="17" />
        </svg>
        <p class="text-sm text-amber-800 dark:text-amber-200">
          All breakpoint queues will be cleared when the process starts.
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
          Input Data (JSON)
        </label>
        <Textarea
          v-model="jsonInput"
          placeholder='{"key": "value"}'
          :rows="10"
          class="font-mono text-sm"
        />
        <p v-if="validationError" class="mt-2 text-sm text-red-600 dark:text-red-400">
          {{ validationError }}
        </p>
        <p v-else class="mt-2 text-sm text-gray-500 dark:text-gray-400">
          Enter JSON data that will be passed as input to the process.
        </p>
      </div>
    </div>

    <template #footer-actions>
      <Button
        variant="outline"
        @click="handleClose"
        :disabled="isRunning"
      >
        Cancel
      </Button>
      <Button
        :loading="isRunning"
        :disabled="!isValid"
        @click="handleRun"
      >
        <template #prepend>
          <svg class="w-4 h-4 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path d="M8 5v14l11-7z"/>
          </svg>
        </template>
        Run Process
      </Button>
    </template>
  </Modal>
</template>

