<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import Textarea from '@/components/ui/datagrid/Textarea.vue'
import type { TrashItem } from '@/types/trash'

interface Props {
  modelValue: boolean
  item: TrashItem
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  save: [data: { headers: Record<string, unknown>; body: Record<string, unknown> }]
  'save-and-approve': [data: { headers: Record<string, unknown>; body: Record<string, unknown> }]
}>()

// Form state
const headersJson = ref('')
const bodyJson = ref('')
const headersError = ref('')
const bodyError = ref('')

// Modal title
const modalTitle = computed(() => {
  return `${props.item.topology} / ${props.item.node}`
})

// Watch for item changes to reset form
watch(
  () => props.item,
  (newItem) => {
    if (newItem) {
      headersJson.value = JSON.stringify(newItem.headers, null, 2)
      bodyJson.value = JSON.stringify(newItem.body, null, 2)
      headersError.value = ''
      bodyError.value = ''
    }
  },
  { immediate: true }
)

// Validate JSON
const validateJson = (jsonString: string): { valid: boolean; parsed?: unknown; error?: string } => {
  try {
    const parsed = JSON.parse(jsonString)
    return { valid: true, parsed }
  } catch (error) {
    return { valid: false, error: error instanceof Error ? error.message : 'Invalid JSON' }
  }
}

// Validate form
const isFormValid = computed(() => {
  const headersValidation = validateJson(headersJson.value)
  const bodyValidation = validateJson(bodyJson.value)
  return headersValidation.valid && bodyValidation.valid
})

// Handle save
const handleSave = () => {
  const headersValidation = validateJson(headersJson.value)
  const bodyValidation = validateJson(bodyJson.value)

  if (!headersValidation.valid) {
    headersError.value = headersValidation.error || 'Invalid JSON'
    return
  }

  if (!bodyValidation.valid) {
    bodyError.value = bodyValidation.error || 'Invalid JSON'
    return
  }

  emit('save', {
    headers: headersValidation.parsed as Record<string, unknown>,
    body: bodyValidation.parsed as Record<string, unknown>,
  })
}

// Handle save and approve
const handleSaveAndApprove = () => {
  const headersValidation = validateJson(headersJson.value)
  const bodyValidation = validateJson(bodyJson.value)

  if (!headersValidation.valid) {
    headersError.value = headersValidation.error || 'Invalid JSON'
    return
  }

  if (!bodyValidation.valid) {
    bodyError.value = bodyValidation.error || 'Invalid JSON'
    return
  }

  emit('save-and-approve', {
    headers: headersValidation.parsed as Record<string, unknown>,
    body: bodyValidation.parsed as Record<string, unknown>,
  })
}

// Clear errors on input
watch(headersJson, (text) => {
  const result = validateJson(text)
  headersError.value = result.valid ? '' : (result.error || 'Invalid JSON')
})

watch(bodyJson, (text) => {
  const result = validateJson(text)
  bodyError.value = result.valid ? '' : (result.error || 'Invalid JSON')
})
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="edit-message-modal"
    :title="modalTitle"
    size="xl"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <!-- Modal Body -->
    <div class="space-y-4">
      <!-- Headers -->
      <div>
        <label
          for="headers-textarea"
          class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
        >
          Headers
        </label>
        <Textarea
          id="headers-textarea"
          v-model="headersJson"
          :rows="8"
          placeholder='{"Content-Type": "application/json"}'
          :error="headersError"
        />
      </div>

      <!-- Body -->
      <div>
        <label
          for="body-textarea"
          class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
        >
          Body
        </label>
        <Textarea
          id="body-textarea"
          v-model="bodyJson"
          :rows="12"
          placeholder='{"key": "value"}'
          :error="bodyError"
        />
      </div>
    </div>

    <!-- Modal Footer -->
    <template #footer-actions>
      <div class="flex items-center justify-end gap-3">
        <Button variant="outline" @click="$emit('update:modelValue', false)">
          Close
        </Button>
        <Button variant="primary" :disabled="!isFormValid" @click="handleSave">
          Save
        </Button>
        <Button variant="primary" :disabled="!isFormValid" @click="handleSaveAndApprove">
          Save and Approve
        </Button>
      </div>
    </template>
  </Modal>
</template>

