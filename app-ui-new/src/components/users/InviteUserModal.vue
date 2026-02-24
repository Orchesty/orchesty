<script setup lang="ts">
import { ref, watch } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import { inviteUsers } from '@/services/usersService'
import { useToast } from '@/composables/useToast'

const { showToast } = useToast()

interface Props {
  modelValue: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'users-invited': []
}>()

const emailInput = ref('')
const emails = ref<Set<string>>(new Set())
const emailsArray = ref<string[]>([])
const submitting = ref(false)

watch(() => props.modelValue, (newValue) => {
  if (!newValue) {
    // Reset form when modal closes
    emailInput.value = ''
    emails.value = new Set()
    emailsArray.value = []
  }
})

const isValidEmail = (email: string): boolean => {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
}

const handleKeyDown = (event: KeyboardEvent) => {
  if (event.key === 'Enter' || event.key === ',') {
    event.preventDefault()
    addEmail()
  }
}

const addEmail = () => {
  const trimmed = emailInput.value.trim().toLowerCase().replace(/,$/, '')
  
  if (!trimmed) return
  
  if (!isValidEmail(trimmed)) {
    // Could show error message
    return
  }
  
  if (emails.value.has(trimmed)) {
    emailInput.value = ''
    return
  }
  
  emails.value.add(trimmed)
  emailsArray.value = Array.from(emails.value)
  emailInput.value = ''
}

const removeEmail = (email: string) => {
  emails.value.delete(email)
  emailsArray.value = Array.from(emails.value)
}

const handleSubmit = async () => {
  if (emailsArray.value.length === 0) return
  
  submitting.value = true
  try {
    await inviteUsers(emailsArray.value)
    emit('users-invited')
    emit('update:modelValue', false)
    showToast('Invitations sent successfully', 'success')
  } catch (error) {
    console.error('Failed to invite users:', error)
    showToast('Failed to send invitations', 'error')
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
    id="invite-user-modal"
    title="Invite users"
    size="md"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <form @submit.prevent="handleSubmit">
      <div class="w-full max-w-xl mb-4">
        <label for="invite-input" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
          Invite users by email
        </label>

        <div class="flex flex-wrap items-center gap-2 p-2 border border-gray-300 rounded-lg bg-gray-50 focus-within:ring-2 focus-within:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
          <!-- Email badges -->
          <span
            v-for="email in emailsArray"
            :key="email"
            class="flex items-center gap-1 text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded dark:bg-blue-900 dark:text-blue-200"
          >
            {{ email }}
            <button
              type="button"
              @click="removeEmail(email)"
              class="text-blue-500 hover:text-blue-700 dark:hover:text-blue-300 text-xs ml-1"
              aria-label="Remove"
            >
              &times;
            </button>
          </span>

          <!-- Input field -->
          <input
            v-model="emailInput"
            type="text"
            id="invite-input"
            placeholder="Enter email and press Enter"
            @keydown="handleKeyDown"
            @blur="addEmail"
            class="flex-grow outline-none border-none border-0 text-sm bg-transparent focus:ring-0 dark:text-white p-0 min-w-[200px]"
          />
        </div>
      </div>
    </form>

    <template #footer-actions>
      <Button variant="outline" @click="handleClose" :disabled="submitting">
        Cancel
      </Button>
      <Button @click="handleSubmit" :disabled="emailsArray.length === 0 || submitting">
        Send invites
      </Button>
    </template>
  </Modal>
</template>

