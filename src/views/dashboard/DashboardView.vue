<script setup lang="ts">
import ProcessesTab from '@/components/dashboard/ProcessesTab.vue'
</script>

<template>
  <main class="h-full overflow-y-auto">
    <div class="px-4 pb-4 pt-6">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Processes</h1>
      </div>
      <ProcessesTab />
    </div>
  </main>
</template>
