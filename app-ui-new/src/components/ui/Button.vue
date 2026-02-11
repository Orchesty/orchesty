<!-- eslint-disable vue/multi-word-component-names -->
<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/utils/cn'

type ButtonVariant = 'primary' | 'outline' | 'danger' | 'success'

interface Props {
  variant?: ButtonVariant
  type?: 'button' | 'submit' | 'reset'
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'primary',
  type: 'button',
})

const buttonClasses = computed(() => {
  const base =
    'inline-flex items-center justify-center rounded-full px-4 py-2 text-center text-sm font-medium focus:outline-none transition-colors disabled:opacity-50 disabled:cursor-not-allowed'

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

  return cn(base, variants[props.variant])
})
</script>

<template>
  <button :type="type" :class="buttonClasses">
    <slot name="prepend" />
    <slot />
    <slot name="append" />
  </button>
</template>

