<script setup lang="ts">
defineProps<{
  maxMessages: number
}>()
</script>

<template>
  <span class="whitespace-nowrap font-medium text-gray-900 dark:text-white">
    {{ maxMessages }}
  </span>
</template>
