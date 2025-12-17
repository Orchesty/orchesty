<script setup lang="ts">
import AppNavbar from '@/components/layout/AppNavbar.vue'
import AppSidebar from '@/components/layout/AppSidebar.vue'
</script>

<template>
  <div class="flex h-screen flex-col overflow-hidden bg-gray-50 dark:bg-gray-900">
    <AppNavbar />
    <div class="flex flex-1 overflow-hidden">
      <AppSidebar />
      <div id="main-content" class="flex-1 overflow-hidden bg-gray-50 dark:bg-gray-900">
        <main class="h-full overflow-y-auto">
          <div class="px-4 pb-4 pt-6">
            <slot />
          </div>
        </main>
      </div>
    </div>
  </div>
</template>

