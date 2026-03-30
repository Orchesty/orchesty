<script setup lang="ts">
import { RouterView } from 'vue-router'
import { AppNavbar, AppSidebar } from '@orchesty/ui-core'
import type { SidebarItem } from '@orchesty/ui-core'
import { Bot, BotMessageSquare } from 'lucide-vue-next'
import TraceDrawer from '@/components/trace/TraceDrawer.vue'
import { useTraceDrawer } from '@/composables/useTraceDrawer'
import type { ChatMessage } from '@/types/trace'

const { isTraceDrawerOpen, toggleDrawer } = useTraceDrawer()

const handleSaveReport = (_message: ChatMessage) => {
  // TODO: Implement save report functionality
}

const enterpriseSidebarItems: SidebarItem[] = [
  { id: 'trace', label: 'Trace', path: '/trace', icon: Bot, iconStrokeWidth: 1.6, iconSizeClass: 'h-7 w-7', insertAfter: 'dashboard' },
]

const enterpriseMenuItems = [
  { type: 'link' as const, label: 'Audit logs', to: '/audit-logs' },
]
</script>

<template>
  <div class="flex h-screen flex-col overflow-hidden bg-gray-50 dark:bg-gray-900">
    <AppNavbar :extra-menu-items="enterpriseMenuItems">
      <template #extra-nav-buttons>
        <button
          type="button"
          @click="toggleDrawer"
          class="mx-2 inline-flex items-center rounded-lg p-1 text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
        >
          <span class="sr-only">Toggle Trace</span>
          <BotMessageSquare class="h-6 w-6" aria-hidden="true" />
        </button>
      </template>
    </AppNavbar>
    <div class="flex flex-1 overflow-hidden">
      <AppSidebar :extra-items="enterpriseSidebarItems" />
      <div id="main-content" class="flex-1 overflow-hidden bg-gray-50 dark:bg-gray-900">
        <RouterView />
      </div>
    </div>

    <TraceDrawer v-model="isTraceDrawerOpen" @save="handleSaveReport" />
  </div>
</template>
