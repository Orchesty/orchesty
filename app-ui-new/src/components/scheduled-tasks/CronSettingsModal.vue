<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import type { ScheduledTask } from '@/types/scheduled-tasks'
import { validateCrontab, getCrontabDescription } from '@/utils/crontabValidator'

interface Props {
  modelValue: boolean
  task: ScheduledTask | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  save: [taskId: string, crontab: string]
}>()

const crontabInput = ref('')
const validationError = ref<string | null>(null)
const isValidating = ref(false)

// Watch for task changes to populate the input
watch(
  () => props.task,
  (newTask) => {
    if (newTask) {
      crontabInput.value = newTask.crontab || ''
      validationError.value = null
    }
  },
  { immediate: true }
)

// Real-time validation
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

// Computed description
const crontabDescription = computed(() => {
  if (!crontabInput.value || validationError.value) {
    return null
  }
  return getCrontabDescription(crontabInput.value)
})

// Check if form is valid
const isFormValid = computed(() => {
  return crontabInput.value.trim() !== '' && !validationError.value
})

const handleClose = () => {
  emit('update:modelValue', false)
}

const handleSave = () => {
  if (!isFormValid.value || !props.task) {
    return
  }

  emit('save', props.task.id, crontabInput.value.trim())
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
      <!-- Crontab Input -->
      <div>
        <label for="crontab-input" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          <span v-if="task">{{ task.topology }} / {{ task.name }}</span>
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
        
        <!-- Description -->
        <p v-else-if="crontabDescription" class="mt-2 text-xs text-green-600 dark:text-green-400">
          {{ crontabDescription }}
        </p>
        
        <!-- Help Text -->
        <p v-else class="mt-2 text-xs text-gray-500 dark:text-gray-400">
          Format: minute hour day month weekday (e.g., 0 2 * * * for daily at 2:00 AM)
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

