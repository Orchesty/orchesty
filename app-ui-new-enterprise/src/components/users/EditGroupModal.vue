<script setup lang="ts">
import { ref, watch } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import { updateGroup } from '@/services/groupsService'
import type { Group } from '@/types/users'

interface Props {
  modelValue: boolean
  group: Group | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'group-updated': []
}>()

const groupName = ref('')
const submitting = ref(false)
const errorMessage = ref('')

watch(() => props.modelValue, (open) => {
  if (open && props.group) {
    groupName.value = props.group.name
    errorMessage.value = ''
  }
})

const handleSubmit = async () => {
  if (!groupName.value.trim() || !props.group) return

  submitting.value = true
  errorMessage.value = ''
  try {
    await updateGroup(props.group.id, {
      name: groupName.value.trim(),
    })
    emit('group-updated')
    emit('update:modelValue', false)
  } catch (error: unknown) {
    let message = 'Failed to update group'
    if (error && typeof error === 'object' && 'response' in error) {
      const axiosErr = error as { response?: { data?: { message?: string } } }
      message = axiosErr.response?.data?.message || message
    }
    errorMessage.value = message
  } finally {
    submitting.value = false
  }
}

const handleClose = () => {
  emit('update:modelValue', false)
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="edit-group-modal"
    title="Edit group"
    size="md"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <form @submit.prevent="handleSubmit">
      <div class="space-y-4">
        <div>
          <label for="edit-group-name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
            Group name <span class="text-red-500">*</span>
          </label>
          <input
            v-model="groupName"
            type="text"
            id="edit-group-name"
            required
            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
            placeholder="Enter group name"
          />
        </div>

        <p v-if="errorMessage" class="text-sm text-red-600 dark:text-red-400">
          {{ errorMessage }}
        </p>
      </div>
    </form>

    <template #footer-actions>
      <Button variant="outline" @click="handleClose" :disabled="submitting">
        Cancel
      </Button>
      <Button @click="handleSubmit" :disabled="!groupName.trim() || submitting">
        Save
      </Button>
    </template>
  </Modal>
</template>
