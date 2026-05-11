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
const submitting = ref(false)
const errorMessage = ref('')

watch(() => props.modelValue, (newValue) => {
  if (!newValue) {
    groupName.value = ''
    errorMessage.value = ''
  }
})

const handleSubmit = async () => {
  if (!groupName.value.trim()) return

  submitting.value = true
  errorMessage.value = ''
  try {
    await createGroup(groupName.value.trim())
    emit('group-created')
    emit('update:modelValue', false)
  } catch (error: unknown) {
    let message = 'Failed to create group'
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
    id="create-group-modal"
    title="Create group"
    size="md"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <form @submit.prevent="handleSubmit">
      <div class="space-y-4">
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

        <p v-if="errorMessage" class="text-sm text-red-600 dark:text-red-400">
          {{ errorMessage }}
        </p>

        <p class="text-sm text-gray-500 dark:text-gray-400">
          After creating the group, you can add users and assign per-topology access in the group detail.
        </p>
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
