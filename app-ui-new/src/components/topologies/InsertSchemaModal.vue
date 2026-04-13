<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import { saveTopologySchema } from '@/services/topologiesService'
import { useToast } from '@/composables/useToast'

interface Props {
  modelValue: boolean
  topologyId: string
  topologyName: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'inserted': []
}>()

const { showToast } = useToast()

const saving = ref(false)
const selectedFile = ref<File | null>(null)
const parsedSchema = ref<Record<string, unknown> | null>(null)
const fileError = ref('')
const isDragging = ref(false)

const canSubmit = computed(() =>
  parsedSchema.value && !fileError.value && !saving.value,
)

const validateSchema = (data: unknown): data is Record<string, unknown> => {
  if (!data || typeof data !== 'object' || Array.isArray(data)) return false
  const obj = data as Record<string, unknown>
  return Array.isArray(obj.nodes) && Array.isArray(obj.connections)
}

const processFile = (file: File) => {
  fileError.value = ''
  parsedSchema.value = null

  if (!file.name.endsWith('.json')) {
    fileError.value = 'File must be a .json or .tplg.json file'
    selectedFile.value = null
    return
  }

  selectedFile.value = file

  const reader = new FileReader()
  reader.onload = (e) => {
    try {
      const data = JSON.parse(e.target?.result as string)
      if (!validateSchema(data)) {
        fileError.value = 'Invalid topology schema: must contain "nodes" and "connections" arrays'
        return
      }
      parsedSchema.value = data
    } catch {
      fileError.value = 'Invalid JSON file'
    }
  }
  reader.readAsText(file)
}

const handleFileChange = (event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (file) processFile(file)
}

const handleDrop = (event: DragEvent) => {
  isDragging.value = false
  const file = event.dataTransfer?.files[0]
  if (file) processFile(file)
}

const handleClose = () => {
  emit('update:modelValue', false)
}

const resetState = () => {
  selectedFile.value = null
  parsedSchema.value = null
  fileError.value = ''
  isDragging.value = false
}

const handleInsert = async () => {
  if (!canSubmit.value || !parsedSchema.value) return

  saving.value = true
  try {
    await saveTopologySchema(props.topologyId, parsedSchema.value)
    showToast('Schema inserted successfully', 'success')
    emit('inserted')
    handleClose()
  } catch (error) {
    console.error('Failed to insert schema:', error)
    showToast('Failed to insert schema', 'error')
  } finally {
    saving.value = false
  }
}

const formatFileSize = (bytes: number): string => {
  if (bytes < 1024) return `${bytes} B`
  return `${(bytes / 1024).toFixed(1)} KB`
}

watch(() => props.modelValue, (newValue) => {
  if (!newValue) resetState()
})
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="insert-schema-modal"
    title="Insert Schema"
    size="md"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <div class="space-y-4">
      <!-- Warning -->
      <div class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-900/30">
        <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
        </svg>
        <div class="text-sm text-amber-800 dark:text-amber-200">
          This will <strong>replace the entire design</strong> of topology
          "<strong>{{ topologyName }}</strong>". This action cannot be undone.
        </div>
      </div>

      <!-- Drop zone / file picker -->
      <div>
        <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          Schema file
          <span class="text-red-600 dark:text-red-400">*</span>
        </label>
        <div
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
          @drop.prevent="handleDrop"
          :class="[
            'relative flex flex-col items-center justify-center rounded-lg border-2 border-dashed p-6 transition-colors',
            isDragging
              ? 'border-primary-500 bg-primary-50 dark:border-primary-400 dark:bg-primary-900/20'
              : fileError
                ? 'border-red-300 bg-red-50 dark:border-red-600 dark:bg-red-900/20'
                : selectedFile && parsedSchema
                  ? 'border-green-300 bg-green-50 dark:border-green-600 dark:bg-green-900/20'
                  : 'border-gray-300 bg-gray-50 hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600',
          ]"
        >
          <template v-if="selectedFile && parsedSchema && !fileError">
            <svg class="mb-2 h-8 w-8 text-green-500 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ selectedFile.name }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ formatFileSize(selectedFile.size) }}</p>
            <button
              type="button"
              class="mt-2 text-xs text-primary-600 hover:underline dark:text-primary-400"
              @click="($refs.fileInput as HTMLInputElement).click()"
            >
              Choose a different file
            </button>
          </template>
          <template v-else>
            <svg class="mb-2 h-8 w-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <p class="mb-1 text-sm text-gray-500 dark:text-gray-400">
              <span class="font-semibold">Click to upload</span> or drag and drop
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">.tplg.json or .json</p>
          </template>
          <input
            ref="fileInput"
            id="insert-schema-file"
            type="file"
            accept=".json"
            class="hidden"
            @change="handleFileChange"
          />
          <label
            v-if="!selectedFile || fileError"
            for="insert-schema-file"
            class="absolute inset-0 cursor-pointer"
          />
        </div>
        <p v-if="fileError" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ fileError }}</p>
      </div>
    </div>

    <template #footer-actions>
      <Button variant="outline" @click="handleClose">
        Cancel
      </Button>
      <Button variant="danger" :disabled="!canSubmit" :loading="saving" @click="handleInsert">
        {{ saving ? 'Replacing...' : 'Replace Schema' }}
      </Button>
    </template>
  </Modal>
</template>
