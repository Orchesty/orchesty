<script setup lang="ts">
import { ref, computed } from 'vue'

type PasswordStrength = 'too-short' | 'weak' | 'fair' | 'strong'

interface Props {
  modelValue: string | null
  label?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  showStrength?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  label: '',
  placeholder: '',
  required: false,
  disabled: false,
  showStrength: false,
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const visible = ref(false)

const inputType = computed(() => (visible.value ? 'text' : 'password'))

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.value)
}

function getPasswordStrength(password: string): PasswordStrength {
  if (password.length < 8) return 'too-short'
  const hasLower = /[a-z]/.test(password)
  const hasUpper = /[A-Z]/.test(password)
  const hasDigit = /\d/.test(password)
  const hasSpecial = /[^a-zA-Z0-9]/.test(password)
  const varietyCount = [hasLower, hasUpper, hasDigit, hasSpecial].filter(Boolean).length
  if (varietyCount <= 1) return 'weak'
  if (varietyCount <= 2) return 'fair'
  return 'strong'
}

const strength = computed(() => getPasswordStrength(props.modelValue || ''))

const strengthSegments = computed(() => {
  const map: Record<PasswordStrength, number> = {
    'too-short': 1,
    'weak': 2,
    'fair': 3,
    'strong': 4,
  }
  return map[strength.value]
})

const strengthColor = computed(() => {
  const map: Record<PasswordStrength, string> = {
    'too-short': 'bg-red-500',
    'weak': 'bg-orange-500',
    'fair': 'bg-yellow-500',
    'strong': 'bg-primary-500',
  }
  return map[strength.value]
})

const strengthLabel = computed(() => {
  const map: Record<PasswordStrength, string> = {
    'too-short': 'Too short',
    'weak': 'Weak',
    'fair': 'Fair',
    'strong': 'Strong',
  }
  return map[strength.value]
})

const strengthTextColor = computed(() => {
  const map: Record<PasswordStrength, string> = {
    'too-short': 'text-red-500',
    'weak': 'text-orange-500',
    'fair': 'text-yellow-500',
    'strong': 'text-primary-500',
  }
  return map[strength.value]
})
</script>

<template>
  <div>
    <label
      v-if="label"
      class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white"
    >
      {{ label }}{{ required ? '*' : '' }}
    </label>
    <div class="relative">
      <input
        :type="inputType"
        :value="modelValue || ''"
        @input="handleInput"
        :placeholder="placeholder"
        :required="required"
        :disabled="disabled"
        class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 pr-10 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
        :class="{ 'cursor-not-allowed': disabled }"
      />
      <button
        type="button"
        tabindex="-1"
        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
        @click="visible = !visible"
      >
        <!-- Eye open -->
        <svg
          v-if="!visible"
          class="h-5 w-5"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7Z" />
        </svg>
        <!-- Eye closed -->
        <svg
          v-else
          class="h-5 w-5"
          aria-hidden="true"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12c1.292 4.338 5.31 7.5 10.066 7.5.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
        </svg>
      </button>
    </div>

    <!-- Strength indicator -->
    <div v-if="showStrength && (modelValue || '').length > 0" class="mt-2">
      <div class="flex gap-1">
        <div
          v-for="i in 4"
          :key="i"
          class="h-1 flex-1 rounded-full transition-colors duration-200"
          :class="i <= strengthSegments ? strengthColor : 'bg-gray-200 dark:bg-gray-600'"
        />
      </div>
      <p class="mt-1 text-xs font-medium" :class="strengthTextColor">
        {{ strengthLabel }}
      </p>
    </div>
  </div>
</template>
