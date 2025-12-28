<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import Textarea from '@/components/ui/datagrid/Textarea.vue'

interface Props {
  modelValue: boolean
  nodeName?: string
  nodeId?: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  run: [jsonData: string]
}>()

const jsonInput = ref('{\n  \n}')
const validationError = ref<string | null>(null)
const isRunning = ref(false)

// Watch for modal open to reset state
watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue) {
      jsonInput.value = '{\n  \n}'
      validationError.value = null
      isRunning.value = false
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
        @click="handleRun"
        :disabled="!isValid || isRunning"
      >
        <svg v-if="isRunning" class="w-4 h-4 me-2 animate-spin" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <svg v-else class="w-4 h-4 me-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
          <path d="M8 5v14l11-7z"/>
        </svg>
        Run Process
      </Button>
    </template>
  </Modal>
</template>

