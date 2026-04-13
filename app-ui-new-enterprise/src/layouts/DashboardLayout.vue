<script setup lang="ts">
import { ref, computed, provide, onMounted, toRef, watch, nextTick } from 'vue'
import { RouterView, useRoute, useRouter } from 'vue-router'
import { AppNavbar, AppSidebar, AUTHORIZATION_KEY, SYSTEM_WORKERS_KEY, provideHelp } from '@orchesty/ui-core'
import type { SidebarItem } from '@orchesty/ui-core'
import { Bell, Bot, Search, Server, Timer, ShieldX, X } from 'lucide-vue-next'
import DropdownMenu, { type DropdownMenuSection } from '@/components/ui/DropdownMenu.vue'
import ConnectorMetricDetailModal from '@/components/dashboard/ConnectorMetricDetailModal.vue'
import FailedMessageModal from '@/components/topologies/FailedMessageModal.vue'
import { useNotificationStream, type InAppNotification } from '@/composables/useNotificationStream'
import { getNotifications } from '@/services/inAppNotificationService'
import { useConnectorMetricDetail } from '@/composables/useConnectorMetricDetail'
import { useFailedMessageModal } from '@/composables/useFailedMessageModal'
import { useFeatures } from '@/composables/useFeatures'
import { usePermissions } from '@/composables/usePermissions'
import { useCloudMode } from '@/composables/useCloudMode'
import { useAuthStore } from '@/stores/auth'
const route = useRoute()
const router = useRouter()
const { metricDetailOpen, selectedRecord } = useConnectorMetricDetail()
const {
  failedMessageOpen,
  failedMessageTopologyId,
  failedMessageNodeId,
  failedMessageCorrelationId,
  failedMessageNodeName,
  failedMessageHideBulkActions,
} = useFailedMessageModal()
const { traceAuditing, auditLogs } = useFeatures()

const { provider, loaded, loadPermissions } = usePermissions()
const { cloudMode, cloudUrl, systemWorkerNames } = useCloudMode()
const authStore = useAuthStore()
provide(AUTHORIZATION_KEY, provider)
provide(SYSTEM_WORKERS_KEY, systemWorkerNames)
provideHelp()

const isChatUserOnly = computed(() => loaded.value && !provider.hasRole('monitoring'))

onMounted(async () => {
  await loadPermissions()
  if (!provider.hasRole('monitoring') && route.path === '/') {
    router.replace('/trace')
  }

  try {
    const result = await getNotifications({ page: 1, limit: 1 })
    if (result.data.length > 0) {
      displayedNotification.value = result.data[0] ?? null
    }
  } catch {
    // non-critical
  }
})

const accessDenied = computed(() => {
  if (!loaded.value) return false
  const permission = route.meta.permission as string | undefined
  if (permission && !provider.can(permission)) return true
  const role = route.meta.role as string | undefined
  if (role && !provider.hasRole(role)) return true
  return false
})

const NOTIF_BAR_KEY = 'notification_bar_visible'
const storedBarVisible = localStorage.getItem(NOTIF_BAR_KEY)
const notificationBarVisible = ref(storedBarVisible !== null ? storedBarVisible === 'true' : true)

function setNotificationBarVisible(val: boolean) {
  notificationBarVisible.value = val
  localStorage.setItem(NOTIF_BAR_KEY, String(val))
}

const { latestNotification, unreadCount } = useNotificationStream()

const displayedNotification = ref<InAppNotification | null>(null)
const transitionKey = ref(0)

watch(latestNotification, (n) => {
  if (!n) return
  displayedNotification.value = null
  nextTick(() => {
    displayedNotification.value = n
    transitionKey.value++
  })
})

const severityDotClass = computed(() => {
  switch (displayedNotification.value?.severity) {
    case 'error':
    case 'danger':
    case 'critical':
      return 'bg-red-500'
    case 'warning':
      return 'bg-yellow-400'
    default:
      return 'bg-blue-500'
  }
})

const eventTypeLabels: Record<string, string> = {
  topology_failed: 'Topology failed',
  topology_failed_message: 'Message trashed',
  topology_slow: 'Slow run',
  topology_burst_failures: 'Burst failures',
}

const notificationSubject = computed(() => {
  const n = displayedNotification.value
  if (!n) return ''
  return eventTypeLabels[n.event_type] || n.event_type.replace(/_/g, ' ')
})

const notificationMenuSections = computed<DropdownMenuSection[]>(() => [
  {
    items: [
      { type: 'link', label: 'Alerts', to: '/notifications' },
      { type: 'custom', slotName: 'notification-bar-toggle' },
    ],
  },
])

const enterpriseSidebarItems = computed<SidebarItem[]>(() => {
  const items: SidebarItem[] = []
  if (traceAuditing.value) {
    items.push({ id: 'trace', label: 'Trace', path: '/trace', icon: Bot, iconStrokeWidth: 1.6, iconSizeClass: 'h-7 w-7', insertAfter: 'dashboard', permission: 'trace:read' })
  }
  items.push({ id: 'resources', label: 'Resources', path: '/resources', icon: Server, role: 'system_manager', insertAfter: 'applications' })
  items.push({ id: 'limiter', label: 'Limiter', path: '/limiter', icon: Timer, iconStrokeWidth: 1.6, iconSizeClass: 'h-7 w-7', role: 'developer', insertAfter: 'trash' })
  return items
})

const onpremMenuItems = computed(() => {
  const items: { type: 'link'; label: string; to: string }[] = []
  if (auditLogs.value && provider.can('settings:read')) {
    items.push({ type: 'link' as const, label: 'Audit logs', to: '/audit-logs' })
  }
  return items
})

const cloudMenuSectionsOverride = computed<DropdownMenuSection[] | undefined>(() => {
  if (!cloudMode.value || !cloudUrl.value) return undefined

  const items: { type: 'link'; label: string; to: string }[] = [
    { type: 'link', label: 'My account', to: `${cloudUrl.value}/account` },
  ]

  if (authStore.user?.isOrgMember) {
    items.push({ type: 'link', label: 'Organization', to: `${cloudUrl.value}/instances` })
  }

  items.push({ type: 'link', label: 'Notifications', to: '/notification-settings' })

  if (provider.can('user:read')) {
    items.push({ type: 'link', label: 'Instance users', to: '/users' })
  }

  if (auditLogs.value && provider.can('settings:read')) {
    items.push({ type: 'link', label: 'Audit logs', to: '/audit-logs' })
  }

  return [
    {
      header: {
        title: authStore.user?.email.split('@')[0] || 'User',
        subtitle: authStore.user?.email || '',
      },
      items,
    },
    {
      items: [
        { type: 'custom', slotName: 'sign-out' },
      ],
    },
  ]
})
</script>

<template>
  <div class="flex h-screen flex-col overflow-hidden bg-gray-50 dark:bg-gray-900">
    <AppNavbar :account-menu-sections-override="cloudMenuSectionsOverride" :extra-menu-items="onpremMenuItems">
      <template #extra-nav-buttons>
        <DropdownMenu
          id="notification-dropdown"
          :sections="notificationMenuSections"
          width="w-52"
        >
          <template #trigger>
            <span class="relative rounded-lg p-2 text-gray-500 hover:bg-gray-100 focus:outline-hidden dark:text-gray-400 dark:hover:bg-gray-700 cursor-pointer">
              <Bell class="h-5 w-5" />
              <span
                v-if="unreadCount > 0"
                class="absolute top-1 right-1 h-2 w-2 rounded-full bg-red-500"
              />
              <span class="sr-only">Notifications</span>
            </span>
          </template>

          <template #notification-bar-toggle>
            <label class="flex w-full cursor-pointer items-center gap-2 px-4 py-2 text-sm text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white">
              <input
                type="checkbox"
                :checked="notificationBarVisible"
                @change="setNotificationBarVisible(!notificationBarVisible)"
                class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
              />
              Notification bar
            </label>
          </template>
        </DropdownMenu>
      </template>
    </AppNavbar>
    <div v-if="notificationBarVisible" class="relative flex h-8 shrink-0 items-center overflow-hidden border-b border-gray-200 bg-white px-4 text-sm dark:border-gray-700 dark:bg-gray-800">
      <div class="flex flex-1 items-center justify-center overflow-hidden">
        <Transition name="notif-fade" mode="out-in">
          <div v-if="displayedNotification" :key="transitionKey" class="flex items-center gap-2 truncate">
            <span :class="['inline-block h-2 w-2 shrink-0 rounded-full', severityDotClass]" />
            <span class="shrink-0 font-medium text-gray-900 dark:text-white">{{ notificationSubject }}</span>
            <span v-if="displayedNotification.topology_name" class="shrink-0 text-gray-400 dark:text-gray-500">·</span>
            <span v-if="displayedNotification.topology_name" class="shrink-0 text-gray-500 dark:text-gray-400">{{ displayedNotification.topology_name }}</span>
            <span class="text-gray-400 dark:text-gray-500">—</span>
            <span class="truncate text-gray-600 dark:text-gray-400">{{ displayedNotification.message }}</span>
            <button
              type="button"
              @click="router.push('/notifications')"
              class="shrink-0 cursor-pointer rounded p-0.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
            >
              <Search class="h-3.5 w-3.5" />
            </button>
          </div>
          <div v-else class="text-gray-400 dark:text-gray-500">No recent notifications</div>
        </Transition>
      </div>
      <button
        type="button"
        @click="setNotificationBarVisible(false)"
        class="absolute right-2 shrink-0 rounded p-0.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300"
      >
        <X class="h-3.5 w-3.5" />
        <span class="sr-only">Close notification bar</span>
      </button>
    </div>
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

    <ConnectorMetricDetailModal v-model="metricDetailOpen" :record="selectedRecord" />
    <FailedMessageModal
      v-model="failedMessageOpen"
      :topology-id="failedMessageTopologyId"
      :node-id="failedMessageNodeId"
      :correlation-id="failedMessageCorrelationId"
      :node-name="failedMessageNodeName"
      :hide-bulk-actions="failedMessageHideBulkActions"
      modal-id="failed-message-modal-global"
    />
  </div>
</template>

<style scoped>
.notif-fade-enter-active,
.notif-fade-leave-active {
  transition: opacity 0.3s ease;
}
.notif-fade-enter-from,
.notif-fade-leave-to {
  opacity: 0;
}
</style>
