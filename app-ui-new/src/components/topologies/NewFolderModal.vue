<script setup lang="ts">
import { ref, watch } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import TextInput from '@/components/ui/datagrid/TextInput.vue'
import { createCategory } from '@/services/topologiesService'
import { useToast } from '@/composables/useToast'

interface Props {
  modelValue: boolean
  parentId?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  parentId: null,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'created': []
}>()

const { showToast } = useToast()

const formData = ref({
  name: ''
})
const saving = ref(false)

const handleClose = () => {
  emit('update:modelValue', false)
  formData.value = { name: '' }
}

const handleCreate = async () => {
  if (!formData.value.name.trim()) return

  saving.value = true
  try {
    await createCategory(formData.value.name.trim(), props.parentId ?? null)
    showToast('Folder created successfully', 'success')
    emit('created')
    handleClose()
  } catch (error) {
    console.error('Failed to create folder:', error)
    showToast('Failed to create folder', 'error')
  } finally {
    saving.value = false
  }
}

watch(() => props.modelValue, (newValue) => {
  if (!newValue) {
    formData.value = { name: '' }
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
      <Button variant="primary" :disabled="saving || !formData.name.trim()" @click="handleCreate">
        {{ saving ? 'Creating...' : 'Create Folder' }}
      </Button>
    </template>
  </Modal>
</template>
