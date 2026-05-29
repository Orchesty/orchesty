<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import FormInput from '@/components/ui/FormInput.vue'
import KeyValueInput from '@/components/ui/KeyValueInput.vue'
import Button from '@/components/ui/Button.vue'
import type { Worker, WorkerType } from '@/types/settings'

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

const formData = ref({
  name: '',
  url: '',
  type: 'http' as WorkerType,
  headers: {} as Record<string, string>,
})

const isTunnel = computed(() => formData.value.type === 'tunnel')

const nameError = ref('')
const urlError = ref('')

watch(
  [() => props.worker, () => props.modelValue],
  () => {
    if (props.modelValue) {
      if (props.worker && props.mode === 'edit') {
        formData.value = {
          name: props.worker.name,
          url: props.worker.url,
          type: props.worker.type ?? 'http',
          headers: { ...props.worker.headers },
        }
      } else {
        formData.value = {
          name: '',
          url: '',
          type: 'http',
          headers: {},
        }
      }
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

  if (!isTunnel.value && !formData.value.url.trim()) {
    urlError.value = 'Hostname is required'
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
    url: isTunnel.value ? '' : formData.value.url,
    type: formData.value.type,
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
      <div>
        <label for="worker-name" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          Name
          <span class="text-red-600 dark:text-red-400">*</span>
        </label>
        <FormInput
          id="worker-name"
          v-model="formData.name"
          placeholder="Enter worker name"
        />
        <p v-if="nameError" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ nameError }}
        </p>
      </div>

      <div>
        <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Type</label>
        <div class="flex items-center gap-4">
          <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
            <input
              type="radio"
              v-model="formData.type"
              value="http"
              class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-blue-600"
            />
            HTTP
          </label>
          <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
            <input
              type="radio"
              v-model="formData.type"
              value="tunnel"
              class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-blue-600"
            />
            Tunnel
          </label>
        </div>
      </div>

      <div v-if="!isTunnel">
        <label for="worker-url" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          Hostname
          <span class="text-red-600 dark:text-red-400">*</span>
        </label>
        <FormInput
          id="worker-url"
          v-model="formData.url"
          placeholder="worker.example.com:3000"
        />
        <p v-if="urlError" class="mt-1 text-sm text-red-600 dark:text-red-400">
          {{ urlError }}
        </p>
      </div>

      <KeyValueInput v-model="formData.headers" />
    </form>

    <template #footer-actions>
      <Button variant="outline" @click="handleClose">Cancel</Button>
      <Button variant="primary" @click="handleSave">Save Worker</Button>
    </template>
  </Modal>
</template>

