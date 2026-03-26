<script setup lang="ts">
defineProps<{
  messages: number
  maxMessages: number
}>()
</script>

<template>
  {{ maxMessages }}
  <span
    :class="messages < maxMessages
      ? 'text-green-600 dark:text-green-400'
      : messages > maxMessages
        ? 'text-red-600 dark:text-red-400'
        : 'text-gray-500 dark:text-gray-400'"
  >
    <svg v-if="messages < maxMessages" class="inline h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V5m0 14-4-4m4 4 4-4"/></svg>
    <svg v-else-if="messages > maxMessages" class="inline h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m0-14 4 4m-4-4-4 4"/></svg>
    ({{ messages }})
  </span>
</template>
