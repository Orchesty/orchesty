<script setup lang="ts">
import { ref, watch, nextTick, computed } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import FormInput from '@/components/ui/FormInput.vue'
import WorkerRemapStep from '@/components/topologies/WorkerRemapStep.vue'
import { createTopology, saveTopologySchema } from '@/services/topologiesService'
import { useToast } from '@/composables/useToast'
import { applyWorkerMapping, extractWorkers } from '@/utils/topologyWorkerMapping'

interface Props {
  modelValue: boolean
  categoryId?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  categoryId: null,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'created': [topologyId: string]
}>()

const { showToast } = useToast()

type Step = 'upload' | 'remap'

const formData = ref({ name: '' })
const saving = ref(false)
const selectedFile = ref<File | null>(null)
const parsedSchema = ref<Record<string, unknown> | null>(null)
const fileError = ref('')
const isDragging = ref(false)
const step = ref<Step>('upload')
const sourceWorkers = ref<string[]>([])
const workerMapping = ref<Record<string, string>>({})
const mappingValid = ref(false)

const canContinue = computed(() =>
  Boolean(formData.value.name.trim() && parsedSchema.value && !fileError.value && !saving.value),
)

const canSubmit = computed(() => {
  if (!parsedSchema.value || saving.value) return false
  if (sourceWorkers.value.length === 0) return true
  return mappingValid.value
})

const submitLabel = computed(() => {
  if (saving.value) return 'Importing...'
  return 'Import'
})

const extractNameFromFile = (filename: string): string => {
  return filename.replace(/\.tplg\.json$/, '').replace(/\.json$/, '')
}

const validateSchema = (data: unknown): data is Record<string, unknown> => {
  if (!data || typeof data !== 'object' || Array.isArray(data)) return false
  const obj = data as Record<string, unknown>
  return Array.isArray(obj.nodes) && Array.isArray(obj.connections)
}

const processFile = (file: File) => {
  fileError.value = ''
  parsedSchema.value = null
  sourceWorkers.value = []
  workerMapping.value = {}
  mappingValid.value = false

  if (!file.name.endsWith('.json')) {
    fileError.value = 'File must be a .json or .tplg.json file'
    selectedFile.value = null
    return
  }

  selectedFile.value = file
  formData.value.name = extractNameFromFile(file.name)

  const reader = new FileReader()
  reader.onload = (e) => {
    try {
      const data = JSON.parse(e.target?.result as string)
      if (!validateSchema(data)) {
        fileError.value = 'Invalid topology schema: must contain "nodes" and "connections" arrays'
        return
      }
      parsedSchema.value = data
      sourceWorkers.value = extractWorkers(data)
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
  formData.value = { name: '' }
  selectedFile.value = null
  parsedSchema.value = null
  fileError.value = ''
  isDragging.value = false
  step.value = 'upload'
  sourceWorkers.value = []
  workerMapping.value = {}
  mappingValid.value = false
}

const handleContinue = () => {
  if (!canContinue.value || !parsedSchema.value) return

  if (sourceWorkers.value.length === 0) {
    void handleImport()
    return
  }

  step.value = 'remap'
}

const handleBack = () => {
  step.value = 'upload'
}

const handleImport = async () => {
  if (!parsedSchema.value || saving.value) return
  if (sourceWorkers.value.length > 0 && !mappingValid.value) return

  saving.value = true
  try {
    const finalSchema = sourceWorkers.value.length > 0
      ? applyWorkerMapping(parsedSchema.value, workerMapping.value)
      : parsedSchema.value
    const result = await createTopology(formData.value.name.trim(), props.categoryId ?? null)
    await saveTopologySchema(result._id, finalSchema)
    showToast('Topology imported successfully', 'success')
    emit('created', result._id)
    handleClose()
  } catch (error) {
    console.error('Failed to import topology:', error)
    showToast('Failed to import topology', 'error')
  } finally {
    saving.value = false
  }
}

const handleShown = () => {
  nextTick(() => {
    document.getElementById('import-topology-file')?.click()
  })
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
    id="import-topology-modal"
    :title="step === 'remap' ? 'Map workers' : 'Import Topology'"
    size="md"
    @update:model-value="emit('update:modelValue', $event)"
    @shown="handleShown"
  >
    <div v-if="step === 'upload'" class="space-y-4">
      <!-- Drop zone / file picker -->
      <div>
        <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          Topology file
          <span class="text-red-600 dark:text-red-400">*</span>
        </label>
        <div
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
          @drop.prevent="handleDrop"
          :class="[
            'flex flex-col items-center justify-center rounded-lg border-2 border-dashed p-6 transition-colors',
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
            id="import-topology-file"
            type="file"
            accept=".json"
            class="hidden"
            @change="handleFileChange"
          />
          <label
            v-if="!selectedFile || fileError"
            for="import-topology-file"
            class="absolute inset-0 cursor-pointer"
          />
        </div>
        <p v-if="fileError" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ fileError }}</p>
      </div>

      <!-- Name -->
      <div>
        <label for="import-topology-name" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          Name
          <span class="text-red-600 dark:text-red-400">*</span>
        </label>
        <FormInput
          id="import-topology-name"
          v-model="formData.name"
          placeholder="Topology name"
        />
      </div>
    </div>

    <WorkerRemapStep
      v-else
      v-model="workerMapping"
      :source-workers="sourceWorkers"
      @update:valid="mappingValid = $event"
    />

    <template #footer-actions>
      <template v-if="step === 'upload'">
        <Button variant="outline" @click="handleClose">
          Cancel
        </Button>
        <Button variant="primary" :disabled="!canContinue" @click="handleContinue">
          {{ sourceWorkers.length === 0 && parsedSchema ? 'Import' : 'Continue' }}
        </Button>
      </template>
      <template v-else>
        <Button variant="outline" @click="handleBack" :disabled="saving">
          Back
        </Button>
        <Button variant="primary" :disabled="!canSubmit" :loading="saving" @click="handleImport">
          {{ submitLabel }}
        </Button>
      </template>
    </template>
  </Modal>
</template>

<style scoped>
/* Make drop zone position relative for the invisible label overlay */
div[class*="border-dashed"] {
  position: relative;
}
</style>
