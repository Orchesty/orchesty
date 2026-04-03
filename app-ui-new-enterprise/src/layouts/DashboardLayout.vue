<script setup lang="ts">
import { computed, provide, onMounted } from 'vue'
import { RouterView, useRoute, useRouter } from 'vue-router'
import { AppNavbar, AppSidebar, AUTHORIZATION_KEY, provideHelp } from '@orchesty/ui-core'
import type { SidebarItem } from '@orchesty/ui-core'
import { Bot, BotMessageSquare, ShieldX } from 'lucide-vue-next'
import TraceDrawer from '@/components/trace/TraceDrawer.vue'
import { useTraceDrawer } from '@/composables/useTraceDrawer'
import { useFeatures } from '@/composables/useFeatures'
import { usePermissions } from '@/composables/usePermissions'
import type { ChatMessage } from '@/types/trace'

const route = useRoute()
const router = useRouter()
const { isTraceDrawerOpen, toggleDrawer } = useTraceDrawer()
const { traceAuditing, auditLogs } = useFeatures()

const { provider, loaded, loadPermissions } = usePermissions()
provide(AUTHORIZATION_KEY, provider)
provideHelp()

const isChatUserOnly = computed(() => loaded.value && !provider.hasRole('monitoring'))

onMounted(async () => {
  await loadPermissions()
  if (!provider.hasRole('monitoring') && route.path === '/') {
    router.replace('/trace')
  }
})

const accessDenied = computed(() => {
  if (!loaded.value) return false
  const permission = route.meta.permission as string | undefined
  if (!permission) return false
  return !provider.can(permission)
})

const handleSaveReport = (_message: ChatMessage) => {
  // TODO: Implement save report functionality
}

const enterpriseSidebarItems = computed<SidebarItem[]>(() => {
  const items: SidebarItem[] = []
  if (traceAuditing.value) {
    items.push({ id: 'trace', label: 'Trace', path: '/trace', icon: Bot, iconStrokeWidth: 1.6, iconSizeClass: 'h-7 w-7', insertAfter: 'dashboard', permission: 'trace:read' })
  }
  return items
})

const enterpriseMenuItems = computed(() => {
  const items: { type: 'link'; label: string; to: string }[] = []
  if (auditLogs.value) {
    items.push({ type: 'link' as const, label: 'Audit logs', to: '/audit-logs' })
  }
  return items
})
</script>

<template>
  <div class="flex h-screen flex-col overflow-hidden bg-gray-50 dark:bg-gray-900">
    <AppNavbar :extra-menu-items="enterpriseMenuItems">
      <template v-if="traceAuditing && !isChatUserOnly" #extra-nav-buttons>
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
      <AppSidebar v-if="!isChatUserOnly" :extra-items="enterpriseSidebarItems" />
      <div id="main-content" class="flex-1 overflow-hidden bg-gray-50 dark:bg-gray-900">
        <div v-if="accessDenied" class="flex h-full items-center justify-center">
          <div class="text-center">
            <ShieldX class="mx-auto mb-4 h-16 w-16 text-gray-300 dark:text-gray-600" :stroke-width="1.2" />
            <h2 class="mb-2 text-xl font-semibold text-gray-900 dark:text-white">Access denied</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">You don't have permission to view this page.</p>
          </div>
        </div>
        <RouterView v-else />
      </div>
    </div>

    <TraceDrawer v-if="traceAuditing" v-model="isTraceDrawerOpen" @save="handleSaveReport" />
  </div>
</template>
