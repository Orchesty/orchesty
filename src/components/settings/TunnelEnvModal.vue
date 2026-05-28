<script setup lang="ts">
import { ref, watch } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import { fetchWorkerEnv } from '@/services/workersService'

interface Props {
  modelValue: boolean
  workerId: string | null
  workerName: string
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const envContent = ref('')
const loading = ref(false)
const copied = ref(false)

watch(
  () => props.modelValue,
  async (open) => {
    if (open && props.workerId) {
      loading.value = true
      copied.value = false
      try {
        envContent.value = await fetchWorkerEnv(props.workerId)
      } catch (error) {
        console.error('Failed to fetch worker env:', error)
        envContent.value = '# Failed to generate environment variables'
      } finally {
        loading.value = false
      }
    }
  },
)

const copyEnv = async () => {
  try {
    await navigator.clipboard.writeText(envContent.value)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (err) {
    console.error('Failed to copy:', err)
  }
}

const handleClose = () => {
  emit('update:modelValue', false)
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    id="tunnel-env-modal"
    :title="`Worker Configuration: ${workerName}`"
    size="lg"
  >
    <div class="space-y-3">
      <p class="text-sm text-gray-500 dark:text-gray-400">
        Copy the following environment variables into the worker's <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-medium dark:bg-gray-700">.env</code> file.
      </p>

      <div v-if="loading" class="flex items-center justify-center py-8">
        <svg class="h-6 w-6 animate-spin text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
        </svg>
      </div>

      <div v-else class="relative">
        <pre class="overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">{{ envContent }}</pre>
        <button
          type="button"
          @click="copyEnv"
          class="absolute right-2 top-2 rounded-lg border border-gray-200 bg-white p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
          :title="copied ? 'Copied!' : 'Copy to clipboard'"
        >
          <svg
            v-if="!copied"
            class="h-4 w-4"
            xmlns="http://www.w3.org/2000/svg"
            height="24px"
            viewBox="0 -960 960 960"
            width="24px"
            fill="currentColor"
          >
            <path d="M360-240q-33 0-56.5-23.5T280-320v-480q0-33 23.5-56.5T360-880h360q33 0 56.5 23.5T800-800v480q0 33-23.5 56.5T720-240H360Zm0-80h360v-480H360v480ZM200-80q-33 0-56.5-23.5T120-160v-560h80v560h440v80H200Zm160-240v-480 480Z" />
          </svg>
          <svg
            v-else
            class="h-4 w-4 text-green-500"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </button>
      </div>
    </div>

    <template #footer-actions>
      <Button variant="outline" @click="copyEnv">
        {{ copied ? 'Copied!' : 'Copy to Clipboard' }}
      </Button>
      <Button variant="primary" @click="handleClose">Done</Button>
    </template>
  </Modal>
</template>
