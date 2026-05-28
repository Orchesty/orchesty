<script setup lang="ts">
export type BadgeVariant = 'green' | 'blue' | 'red' | 'yellow' | 'gray'

defineProps<{
  variant: BadgeVariant
}>()

const variantClasses: Record<BadgeVariant, string> = {
  green: 'bg-primary-100 text-primary-700 dark:bg-primary-800 dark:text-primary-300',
  blue: 'bg-blue-100 text-blue-700 dark:bg-blue-800 dark:text-blue-300',
  red: 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-300',
  yellow: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-800 dark:text-yellow-300',
  gray: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
}
</script>

<template>
  <span
    :class="[
      'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
      variantClasses[variant],
    ]"
  >
    <slot />
  </span>
</template>
