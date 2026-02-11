<script setup lang="ts">
import { ref, watch } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import TextInput from '@/components/ui/datagrid/TextInput.vue'
import { renameCategory } from '@/services/topologiesService'
import { useToast } from '@/composables/useToast'

interface Props {
  modelValue: boolean
  folderId: string
  folderName: string
  parentId?: string | null
}

const props = withDefaults(defineProps<Props>(), {
  parentId: null,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'renamed': []
}>()

const { showToast } = useToast()

const newName = ref('')
const saving = ref(false)

const handleClose = () => {
  emit('update:modelValue', false)
}

const handleRename = async () => {
  if (!newName.value.trim()) return

  saving.value = true
  try {
    await renameCategory(props.folderId, newName.value.trim(), props.parentId ?? null)
    showToast('Folder renamed successfully', 'success')
    emit('renamed')
    handleClose()
  } catch (error) {
    console.error('Failed to rename folder:', error)
    showToast('Failed to rename folder', 'error')
  } finally {
    saving.value = false
  }
}

watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    newName.value = props.folderName
  }
})
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="rename-folder-modal"
    title="Rename Folder"
    size="md"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <form @submit.prevent="handleRename" class="space-y-4">
      <div>
        <label for="rename-folder-name" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          Name
          <span class="text-red-600 dark:text-red-400">*</span>
        </label>
        <TextInput
          id="rename-folder-name"
          v-model="newName"
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
      <Button variant="primary" :disabled="saving || !newName.trim()" @click="handleRename">
        {{ saving ? 'Renaming...' : 'Rename' }}
      </Button>
    </template>
  </Modal>
</template>
