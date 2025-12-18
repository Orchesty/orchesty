<script setup lang="ts">
import { ref, watch } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import TextInput from '@/components/ui/datagrid/TextInput.vue'

interface Props {
  modelValue: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const formData = ref({
  name: ''
})

const handleClose = () => {
  emit('update:modelValue', false)
  // Reset form
  formData.value = {
    name: ''
  }
}

const handleCreate = () => {
  // TODO: Implement create folder logic
  console.log('Create folder:', formData.value)
  handleClose()
}

watch(() => props.modelValue, (newValue) => {
  if (!newValue) {
    // Reset form when modal closes
    formData.value = {
      name: ''
    }
  }
})
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="new-folder-modal"
    title="New Folder"
    size="md"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <form @submit.prevent="handleCreate" class="space-y-4">
      <!-- Name -->
      <div>
        <label for="folder-name" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          Name
          <span class="text-red-600 dark:text-red-400">*</span>
        </label>
        <TextInput
          id="folder-name"
          v-model="formData.name"
          placeholder="Enter folder name"
          width="w-full"
          required
        />
      </div>
    </form>

    <template #footer-actions>
      <Button variant="outline" @click="handleClose">
        Cancel
      </Button>
      <Button variant="primary" @click="handleCreate">
        Create Folder
      </Button>
    </template>
  </Modal>
</template>

