<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import type { ScheduledTask } from '@/types/scheduled-tasks'
import { validateCrontab, getCrontabDescription } from '@/utils/crontabValidator'
import { getNextCronRuns, formatNextRun } from '@/utils/cronParser'

interface Props {
  modelValue: boolean
  task: ScheduledTask | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  save: [taskId: string, crontab: string, params: string]
}>()

const crontabInput = ref('')
const paramsInput = ref('')
const validationError = ref<string | null>(null)
const paramsValidationError = ref<string | null>(null)
const isValidating = ref(false)

// Watch for task changes to populate the input
watch(
  () => props.task,
  (newTask) => {
    if (newTask) {
      crontabInput.value = newTask.crontab || ''
      paramsInput.value = newTask.params || ''
      validationError.value = null
      paramsValidationError.value = null
    }
  },
  { immediate: true }
)

// Real-time validation for crontab
watch(crontabInput, (newValue) => {
  if (!newValue || newValue.trim() === '') {
    validationError.value = null
    return
  }

  isValidating.value = true
  const result = validateCrontab(newValue)
  validationError.value = result.valid ? null : result.error || 'Invalid crontab expression'
  isValidating.value = false
})

// Real-time validation for params
watch(paramsInput, (newValue) => {
  if (!newValue || newValue.trim() === '') {
    paramsValidationError.value = null
    return
  }

  try {
    // Wrap with braces and try to parse as JSON
    const wrapped = `{${newValue}}`
    JSON.parse(wrapped)
    paramsValidationError.value = null
  } catch (error) {
    // Show the JSON parse error message
    paramsValidationError.value = error instanceof Error ? error.message : 'Invalid JSON format'
  }
})

// Computed description
const crontabDescription = computed(() => {
  if (!crontabInput.value || validationError.value) {
    return null
  }
  return getCrontabDescription(crontabInput.value)
})

// Next 2 scheduled run times
const upcomingRuns = computed(() => {
  if (!crontabInput.value || validationError.value) {
    return []
  }
  return getNextCronRuns(crontabInput.value, 2)
})

// Check if form is valid
const isFormValid = computed(() => {
  return crontabInput.value.trim() !== '' && !validationError.value && !paramsValidationError.value
})

const handleClose = () => {
  emit('update:modelValue', false)
}

const handleSave = () => {
  if (!isFormValid.value || !props.task) {
    return
  }

  emit('save', props.task.id, crontabInput.value.trim(), paramsInput.value.trim())
  handleClose()
}

const handleSubmit = (e: Event) => {
  e.preventDefault()
  handleSave()
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="edit-cron-modal"
    title="Edit Crontab"
    size="md"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <!-- Modal Body -->
    <form :id="`edit-cron-form`" class="space-y-4" @submit="handleSubmit">
      <!-- Task Label -->
      <div>
        <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          <span v-if="task">{{ task.topology }} / {{ task.name }}</span>
        </label>
      </div>

      <!-- Crontab Input -->
      <div>
        <label for="crontab-input" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          Crontab Expression
        </label>
        <input
          id="crontab-input"
          v-model="crontabInput"
          type="text"
          name="crontab"
          class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 font-mono text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          :class="{
            'border-red-500 focus:border-red-500 focus:ring-red-500 dark:border-red-400 dark:focus:border-red-400 dark:focus:ring-red-400':
              validationError,
          }"
          placeholder="0 2 * * *"
        />

        <!-- Validation Error -->
        <p v-if="validationError" class="mt-2 text-xs text-red-600 dark:text-red-400">
          {{ validationError }}
        </p>

        <!-- Description & Upcoming Runs -->
        <div v-else-if="crontabDescription || upcomingRuns.length > 0" class="mt-2">
          <p v-if="crontabDescription" class="text-xs text-primary-600 dark:text-primary-400">
            {{ crontabDescription }}
          </p>
          <div v-if="upcomingRuns.length > 0" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            Next: {{ upcomingRuns.map(d => formatNextRun(d)).join(', ') }}
          </div>
        </div>

        <!-- Help Text -->
        <p v-else class="mt-2 text-xs text-gray-500 dark:text-gray-400">
          Format: minute hour day month weekday (e.g., 0 2 * * * for daily at 2:00 AM)
        </p>
      </div>

      <!-- Params Input -->
      <div>
        <label for="params-input" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          Parameters (Optional)
        </label>
        <textarea
          id="params-input"
          v-model="paramsInput"
          name="params"
          rows="3"
          class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 font-mono text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
          :class="{
            'border-red-500 focus:border-red-500 focus:ring-red-500 dark:border-red-400 dark:focus:border-red-400 dark:focus:ring-red-400':
              paramsValidationError,
          }"
          placeholder='"key": "value", "foo": "bar"'
        ></textarea>

        <!-- Validation Error -->
        <p v-if="paramsValidationError" class="mt-2 text-xs text-red-600 dark:text-red-400">
          {{ paramsValidationError }}
        </p>

        <!-- Help Text -->
        <p v-else class="mt-2 text-xs text-gray-500 dark:text-gray-400">
          JSON format without {}. Eg.: "key": "val", "foo": "bar"
        </p>
      </div>
    </form>

    <!-- Modal Footer -->
    <template #footer-actions>
      <Button variant="outline" @click="handleClose">
        Cancel
      </Button>
      <Button variant="primary" type="submit" form="edit-cron-form" :disabled="!isFormValid">
        Save
      </Button>
    </template>
  </Modal>
</template>

