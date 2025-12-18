<script setup lang="ts">
import { ref, watch } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import TextInput from '@/components/ui/datagrid/TextInput.vue'
import KeyValueInput from '@/components/ui/KeyValueInput.vue'
import Button from '@/components/ui/Button.vue'
import type { Worker } from '@/types/settings'

interface Props {
  modelValue: boolean
  worker: Worker | null
  mode: 'create' | 'edit'
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  save: [data: Omit<Worker, 'id'> | Partial<Worker>]
}>()

// Form state
const formData = ref({
  name: '',
  url: '',
  headers: {} as Record<string, string>,
})

// Validation
const nameError = ref('')
const urlError = ref('')

// Initialize form when worker changes or modal opens
watch(
  [() => props.worker, () => props.modelValue],
  () => {
    if (props.modelValue) {
      if (props.worker && props.mode === 'edit') {
        formData.value = {
          name: props.worker.name,
          url: props.worker.url,
          headers: { ...props.worker.headers },
        }
      } else {
        formData.value = {
          name: '',
          url: '',
          headers: {},
        }
      }
      // Reset validation
      nameError.value = ''
      urlError.value = ''
    }
  },
  { immediate: true }
)

const validate = (): boolean => {
  let isValid = true

  if (!formData.value.name.trim()) {
    nameError.value = 'Name is required'
    isValid = false
  } else {
    nameError.value = ''
  }

  if (!formData.value.url.trim()) {
    urlError.value = 'URL is required'
    isValid = false
  } else {
    urlError.value = ''
  }

  return isValid
}

const handleSave = () => {
  if (!validate()) {
    return
  }

  emit('save', {
    name: formData.value.name,
    url: formData.value.url,
    headers: formData.value.headers,
  })
}

const handleClose = () => {
  emit('update:modelValue', false)
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    id="worker-modal"
    :title="mode === 'create' ? 'Add Worker' : 'Edit Worker'"
    size="md"
  >
    <form @submit.prevent="handleSave" class="space-y-4">
      <!-- Name -->
      <div>
        <label for="worker-name" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          Name
          <span class="text-red-600 dark:text-red-400">*</span>
        </label>
        <TextInput
          id="worker-name"
          v-model="formData.name"
          placeholder="Enter worker name"
          width="w-full"
        />
        <p v-if="nameError" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ nameError }}
        </p>
      </div>

      <!-- URL -->
      <div>
        <label for="worker-url" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          URL
          <span class="text-red-600 dark:text-red-400">*</span>
        </label>
        <TextInput
          id="worker-url"
          v-model="formData.url"
          type="text"
          placeholder="https://worker.example.com"
          width="w-full"
        />
        <p v-if="urlError" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ urlError }}
        </p>
      </div>

      <!-- Headers -->
      <KeyValueInput v-model="formData.headers" />
    </form>

    <template #footer-actions>
      <Button variant="outline" @click="handleClose">Cancel</Button>
      <Button variant="primary" @click="handleSave">Save Worker</Button>
    </template>
  </Modal>
</template>

