<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import { inviteUsers, type InviteResult } from '@/services/usersService'
import { useToast } from '@/composables/useToast'

const { showToast } = useToast()

interface Props {
  modelValue: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'user-invited': []
}>()

const emailInput = ref('')
const submitting = ref(false)
const result = ref<InviteResult | null>(null)
const addedDirectly = ref(false)
const copied = ref(false)
const errorMessage = ref('')

const showResults = computed(() => result.value !== null || addedDirectly.value)

watch(() => props.modelValue, (newValue) => {
  if (!newValue) {
    emailInput.value = ''
    result.value = null
    addedDirectly.value = false
    copied.value = false
    errorMessage.value = ''
  }
})

const isValidEmail = (email: string): boolean => {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
}

const canSubmit = computed(() => {
  return emailInput.value.trim() !== '' && isValidEmail(emailInput.value.trim()) && !submitting.value
})

const getInviteLink = (hash: string): string => {
  return `${window.location.origin}/accept-invite/${hash}`
}

const copyLink = async () => {
  if (!result.value?.hash) return
  try {
    await navigator.clipboard.writeText(getInviteLink(result.value.hash))
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  } catch {
    showToast('Failed to copy link', 'error')
  }
}

const handleSubmit = async () => {
  const email = emailInput.value.trim().toLowerCase()
  if (!email || !isValidEmail(email)) return

  submitting.value = true
  errorMessage.value = ''
  try {
    const results = await inviteUsers([email])
    const r = results[0]
    if (!r) {
      errorMessage.value = 'Failed to create invitation'
      return
    }
    if (r.hash) {
      result.value = r
      emit('user-invited')
    } else if (r.added) {
      addedDirectly.value = true
      emit('user-invited')
    } else {
      errorMessage.value = r.error || 'Failed to create invitation'
    }
  } catch (error) {
    console.error('Failed to invite user:', error)
    errorMessage.value = 'Failed to create invitation'
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
    :title="addedDirectly ? 'User added' : showResults ? 'Invite link' : 'Invite user'"
    size="md"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <!-- Input Form -->
    <template v-if="!showResults">
      <form @submit.prevent="handleSubmit">
        <div class="w-full mb-4">
          <label for="invite-input" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
            Email address
          </label>
          <input
            v-model="emailInput"
            type="email"
            id="invite-input"
            placeholder="user@example.com"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
            :class="{ 'border-red-500 dark:border-red-400': errorMessage }"
          />
          <p v-if="errorMessage" class="mt-2 text-xs text-red-600 dark:text-red-400">
            {{ errorMessage }}
          </p>
        </div>
      </form>
    </template>

    <!-- User re-activated -->
    <template v-else-if="addedDirectly">
      <div class="flex flex-col items-center py-4">
        <svg class="mb-3 h-12 w-12 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="text-sm font-medium text-gray-900 dark:text-white">
          User access restored
        </p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
          {{ emailInput }} can now sign in to this instance again.
        </p>
      </div>
    </template>

    <!-- Invite link result -->
    <template v-else>
      <div class="mb-3 flex items-start gap-2 rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-900/20">
        <svg class="mt-0.5 h-4 w-4 shrink-0 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M22 2 11 13" /><path d="m22 2-7 20-4-9-9-4 20-7z" />
        </svg>
        <p class="text-sm text-green-700 dark:text-green-300">
          An invitation email has been sent to <strong>{{ result!.email }}</strong>.
        </p>
      </div>
      <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
        You can also share this link directly with the user.
      </p>

      <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
        <div class="mb-2 text-sm font-medium text-gray-900 dark:text-white">
          {{ result!.email }}
        </div>
        <div class="flex items-center gap-2">
          <input
            type="text"
            readonly
            :value="getInviteLink(result!.hash!)"
            class="flex-1 rounded-md border border-gray-300 bg-gray-50 px-2.5 py-1.5 text-xs font-mono text-gray-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
            @click="($event.target as HTMLInputElement).select()"
          />
          <button
            type="button"
            @click="copyLink"
            class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
          >
            <svg v-if="copied" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5 text-primary-500">
              <polyline points="20 6 9 17 4 12" />
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
              <rect width="14" height="14" x="8" y="8" rx="2" ry="2" /><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
            </svg>
            {{ copied ? 'Copied' : 'Copy' }}
          </button>
        </div>
      </div>
    </template>

    <template #footer-actions>
      <template v-if="!showResults">
        <Button variant="outline" @click="handleClose" :disabled="submitting">
          Cancel
        </Button>
        <Button @click="handleSubmit" :disabled="!canSubmit">
          {{ submitting ? 'Generating...' : 'Generate invite link' }}
        </Button>
      </template>
      <template v-else>
        <Button variant="primary" @click="handleClose">
          Done
        </Button>
      </template>
    </template>
  </Modal>
</template>
