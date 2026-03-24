<script setup lang="ts">
import { ref } from 'vue'
import Drawer from '@/components/ui/Drawer.vue'
import Button from '@/components/ui/Button.vue'
import Confirm from '@/components/ui/Confirm.vue'
import { removeUser } from '@/services/usersService'
import { useToast } from '@/composables/useToast'
import { useDateFormat } from '@/composables/useDateFormat'
import type { User } from '@/types/users'

const { showToast } = useToast()
const { formatDateTime } = useDateFormat()

interface Props {
  modelValue: boolean
  user: User | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'user-updated': []
  'user-removed': []
}>()

const confirmRemoveOpen = ref(false)

const handleConfirmRemove = async () => {
  if (!props.user) return
  try {
    await removeUser(props.user.id)
    confirmRemoveOpen.value = false
    emit('user-removed')
    showToast('User removed successfully', 'success')
  } catch (error) {
    console.error('Failed to remove user:', error)
    showToast('Failed to remove user', 'error')
  }
}
</script>

<template>
  <Drawer
    :model-value="modelValue"
    id="user-detail-drawer"
    label="USER DETAILS"
    width="w-96"
    placement="right"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <div v-if="user" class="space-y-6">
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <div class="py-3 mb-4 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">User Information</h3>
        </div>
        <div class="space-y-4">
          <dl class="flex items-center justify-between gap-4">
            <dt class="text-sm font-normal text-gray-500 dark:text-gray-400">Email</dt>
            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ user.email }}</dd>
          </dl>
          <dl class="flex items-center justify-between gap-4">
            <dt class="text-sm font-normal text-gray-500 dark:text-gray-400">Created</dt>
            <dd class="text-sm font-medium text-gray-900 dark:text-white">{{ formatDateTime(user.created) }}</dd>
          </dl>
        </div>
      </div>
    </div>

    <template #footer-actions>
      <div class="flex items-center justify-between w-full">
        <Button variant="danger" @click="confirmRemoveOpen = true">
          Remove
        </Button>
        <Button variant="outline" @click="emit('update:modelValue', false)">
          Close
        </Button>
      </div>
    </template>
  </Drawer>

  <Confirm
    v-if="user"
    v-model="confirmRemoveOpen"
    id="confirm-remove-user-modal"
    confirm-text="Yes, remove"
    cancel-text="Cancel"
    @confirm="handleConfirmRemove"
    @cancel="confirmRemoveOpen = false"
  >
    <p class="text-sm text-gray-700 dark:text-gray-300">
      Are you sure you want to remove <strong>{{ user.email }}</strong> from the system? This action cannot be undone.
    </p>
  </Confirm>
</template>
