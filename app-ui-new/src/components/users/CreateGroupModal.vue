<script setup lang="ts">
import { ref, watch } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import { createGroup } from '@/services/groupsService'

interface Props {
  modelValue: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'group-created': []
}>()

const groupName = ref('')
const selectedModules = ref<string[]>([])
const submitting = ref(false)

const allModules = [
  { id: 'dashboard', label: 'Dashboard' },
  { id: 'ai-assistant', label: 'AI Assistant' },
  { id: 'integrations', label: 'Integrations' },
  { id: 'analytics', label: 'Analytics' },
  { id: 'settings', label: 'Settings' }
]

watch(() => props.modelValue, (newValue) => {
  if (!newValue) {
    // Reset form when modal closes
    groupName.value = ''
    selectedModules.value = []
  }
})

const handleModuleChange = (moduleId: string, checked: boolean) => {
  if (checked) {
    selectedModules.value.push(moduleId)
  } else {
    selectedModules.value = selectedModules.value.filter(id => id !== moduleId)
  }
}

const handleSubmit = async () => {
  if (!groupName.value.trim()) return
  
  submitting.value = true
  try {
    await createGroup({
      name: groupName.value,
      modules: selectedModules.value
    })
    emit('group-created')
    emit('update:modelValue', false)
  } catch (error) {
    console.error('Failed to create group:', error)
  } finally {
    submitting.value = false
  }
}

const handleClose = () => {
  emit('update:modelValue', false)
}

const isModuleChecked = (moduleId: string) => {
  return selectedModules.value.includes(moduleId)
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="create-group-modal"
    title="Create group"
    size="md"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <form @submit.prevent="handleSubmit">
      <div class="space-y-4">
        <!-- Group Name -->
        <div>
          <label for="group-name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
            Group name <span class="text-red-500">*</span>
          </label>
          <input
            v-model="groupName"
            type="text"
            id="group-name"
            required
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
            placeholder="Enter group name"
          />
        </div>

        <!-- Modules -->
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
            Modules
          </label>
          <div class="space-y-3">
            <div v-for="module in allModules" :key="module.id" class="flex items-center">
              <input
                :id="`create-module-${module.id}`"
                type="checkbox"
                :checked="isModuleChecked(module.id)"
                @change="handleModuleChange(module.id, ($event.target as HTMLInputElement).checked)"
                class="w-4 h-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
              >
              <label :for="`create-module-${module.id}`" class="ms-2 text-sm text-gray-900 dark:text-gray-300">
                {{ module.label }}
              </label>
            </div>
          </div>
        </div>
      </div>
    </form>

    <template #footer-actions>
      <Button variant="outline" @click="handleClose" :disabled="submitting">
        Cancel
      </Button>
      <Button @click="handleSubmit" :disabled="!groupName.trim() || submitting">
        Create
      </Button>
    </template>
  </Modal>
</template>

