<script setup lang="ts">
import { ref, computed } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import type { Token } from '@/types/settings'

interface Props {
  modelValue: boolean
  token: Token | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const copied = ref(false)

const tokenValue = computed(() => props.token?.tokenValue || '')

const copyToken = async () => {
  if (!tokenValue.value) return

  try {
    await navigator.clipboard.writeText(tokenValue.value)
    copied.value = true
    setTimeout(() => {
      copied.value = false
    }, 2000)
  } catch (err) {
    console.error('Failed to copy token:', err)
  }
}

const handleClose = () => {
  emit('update:modelValue', false)
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    id="view-token-modal"
    title="Your Token"
    size="md"
  >
    <div class="space-y-4">
      <div class="rounded-lg bg-yellow-50 p-4 text-sm text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300">
        <p class="font-medium">Important: Save this token securely</p>
        <p class="mt-1">You won't be able to see it again after closing this window.</p>
      </div>

      <div>
        <div class="flex gap-2">
          <input
            type="text"
            readonly
            :value="tokenValue"
            class="block flex-1 rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-primary-500 dark:focus:ring-primary-500"
          />
          <Button variant="primary" @click="copyToken">
            <svg
              v-if="!copied"
              class="w-5 h-5 mr-2"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              height="24px"
              viewBox="0 -960 960 960"
              width="24px"
              fill="currentColor"
            >
              <path
                d="M360-240q-33 0-56.5-23.5T280-320v-480q0-33 23.5-56.5T360-880h360q33 0 56.5 23.5T800-800v480q0 33-23.5 56.5T720-240H360Zm0-80h360v-480H360v480ZM200-80q-33 0-56.5-23.5T120-160v-560h80v560h440v80H200Zm160-240v-480 480Z"
              />
            </svg>
            <svg
              v-else
              class="w-5 h-5 mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ copied ? 'Copied!' : 'Copy' }}
          </Button>
        </div>
      </div>
    </div>

    <template #footer-actions>
      <Button variant="outline" @click="handleClose">Done</Button>
    </template>
  </Modal>
</template>

