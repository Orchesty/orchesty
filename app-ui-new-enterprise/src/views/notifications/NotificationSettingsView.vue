<script setup lang="ts">
import NotificationSettings from '@/components/account/NotificationSettings.vue'
</script>

<template>
  <div class="relative h-full overflow-y-auto px-4 pt-6">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Notification settings</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Manage your notification preferences
      </p>
    </div>

    <NotificationSettings />
  </div>
</template>
