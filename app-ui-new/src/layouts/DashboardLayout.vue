<script setup lang="ts">
import AppNavbar from '@/components/layout/AppNavbar.vue'
import AppSidebar from '@/components/layout/AppSidebar.vue'
import TraceDrawer from '@/components/trace/TraceDrawer.vue'
import { useTraceDrawer } from '@/composables/useTraceDrawer'
import type { ChatMessage } from '@/types/trace'

// TraceDrawer state from composable
const { isTraceDrawerOpen } = useTraceDrawer()

const handleSaveReport = (message: ChatMessage) => {
  console.log('Save report from TraceDrawer:', message)
  // TODO: Implement save report functionality
}
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
    
    <!-- Trace Drawer -->
    <TraceDrawer v-model="isTraceDrawerOpen" @save="handleSaveReport" />
  </div>
</template>

