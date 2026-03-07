<!-- eslint-disable vue/multi-word-component-names -->
<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/utils/cn'

type ButtonVariant = 'primary' | 'outline' | 'danger' | 'success'

interface Props {
  variant?: ButtonVariant
  type?: 'button' | 'submit' | 'reset'
  loading?: boolean
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  type: 'button',
  loading: false,
  disabled: false,
})

const isDisabled = computed(() => props.loading || props.disabled)

const buttonClasses = computed(() => {
  const base =
    'inline-flex items-center justify-center rounded-full px-4 py-2 text-center text-sm font-medium focus:outline-none transition-colors disabled:cursor-not-allowed disabled:opacity-50'

  const disabledStyle =
    'border border-gray-200 bg-white text-gray-400 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-500'

  const variants = {
    primary:
      'bg-primary-500 text-black hover:bg-primary-600',
    outline:
      'border border-gray-200 bg-white text-gray-900 hover:bg-gray-100 hover:text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white',
    danger:
      'bg-red-600 text-white hover:bg-red-700 focus:ring-4 focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800',
    success:
      'bg-green-600 text-white hover:bg-green-700 focus:ring-4 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800',
  }

  return cn(base, isDisabled.value ? disabledStyle : variants[props.variant])
})
</script>

<template>
  <button :type="type" :class="buttonClasses" :disabled="loading || disabled">
    <svg v-if="loading" class="h-4 w-4 me-2 animate-spin" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
    </svg>
    <slot v-else name="prepend" />
    <slot />
    <slot name="append" />
  </button>
</template>

