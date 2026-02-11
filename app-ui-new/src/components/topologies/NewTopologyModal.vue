<script setup lang="ts">
import { ref, watch } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import TextInput from '@/components/ui/datagrid/TextInput.vue'
import Textarea from '@/components/ui/datagrid/Textarea.vue'

interface Props {
  modelValue: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const formData = ref({
  name: '',
  description: ''
})

const handleClose = () => {
  emit('update:modelValue', false)
  // Reset form
  formData.value = {
    name: '',
    description: ''
  }
}

const handleCreate = () => {
  // TODO: Implement create topology logic
  console.log('Create topology:', formData.value)
  handleClose()
}

watch(() => props.modelValue, (newValue) => {
  if (!newValue) {
    // Reset form when modal closes
    formData.value = {
      name: '',
      description: ''
    }
  }
})
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="new-topology-modal"
    title="New Topology"
    size="md"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <form @submit.prevent="handleCreate" class="space-y-4">
      <!-- Name -->
      <div>
        <label for="topology-name" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          Name
          <span class="text-red-600 dark:text-red-400">*</span>
        </label>
        <TextInput
          id="topology-name"
          v-model="formData.name"
          placeholder="Enter topology name"
          width="w-full"
          required
        />
      </div>

      <!-- Description -->
      <div>
        <label for="topology-description" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          Description
        </label>
        <Textarea
          id="topology-description"
          v-model="formData.description"
          placeholder="Enter topology description (optional)"
          :rows="3"
        />
      </div>
    </form>

    <template #footer-actions>
      <Button variant="outline" @click="handleClose">
        Cancel
      </Button>
      <Button variant="primary" @click="handleCreate">
        Create Topology
      </Button>
    </template>
  </Modal>
</template>

