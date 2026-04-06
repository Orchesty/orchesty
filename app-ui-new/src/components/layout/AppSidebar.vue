<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { ChartPie, Clock, OctagonAlert, List, Workflow, Grip, Settings } from 'lucide-vue-next'
import { useSidebar } from '@/composables/useSidebar'
import { useCronAlerts } from '@/composables/useCronAlerts'
import { useAuthorization } from '@/composables/useAuthorization'
import type { SidebarItem } from '@/config/navigation'

interface Props {
  extraItems?: SidebarItem[]
}

const props = withDefaults(defineProps<Props>(), {
  extraItems: () => [],
})

const route = useRoute()
const { can, hasRole } = useAuthorization()

const isActive = (path: string) => {
  return route.path.startsWith(path)
}

const iconColorClass = (path: string) => {
  return isActive(path)
    ? 'shrink-0 text-primary-600 dark:text-primary-500'
    : 'shrink-0 text-gray-400 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white'
}

useSidebar()

const { hasMisconfiguredCrons, refresh: refreshCronAlerts } = useCronAlerts()

onMounted(() => {
  refreshCronAlerts()
})

const coreItems: SidebarItem[] = [
  { id: 'dashboard', label: 'Control Center', path: '/dashboard', icon: ChartPie, permission: 'overview:read' },
  { id: 'scheduled-tasks', label: 'Scheduled Tasks', path: '/scheduled-tasks', icon: Clock, badge: 'cron-alerts', permission: 'scheduled_task:read' },
  { id: 'trash', label: 'Failed Messages', path: '/trash', icon: OctagonAlert, permission: 'user_task:read' },
  { id: 'logs', label: 'Logs', path: '/logs', icon: List, permission: 'logs:read' },
  { id: 'topologies', label: 'Topologies', path: '/topologies', icon: Workflow, permission: 'topology:read' },
  { id: 'applications', label: 'Applications', path: '/applications', icon: Grip, permission: 'application:read' },
  { id: 'settings', label: 'Settings', path: '/settings', icon: Settings, permission: 'settings:read' },
]

const allItems = (() => {
  const result = [...coreItems]
  const appendItems: SidebarItem[] = []

  for (const item of props.extraItems) {
    if (item.insertAfter) {
      const idx = result.findIndex((i) => i.id === item.insertAfter)
      if (idx !== -1) {
        result.splice(idx + 1, 0, item)
        continue
      }
    }
    appendItems.push(item)
  }

  return [...result, ...appendItems]
})()

const visibleItems = computed(() =>
  allItems.filter((item) => {
    if (item.permission && !can(item.permission)) return false
    if (item.role && !hasRole(item.role)) return false
    return true
  }),
)
</script>

<template>
  <aside
    id="orchesty-sidebar"
    class="h-full w-16 bg-white transition-width duration-75 dark:bg-gray-800"
    aria-label="Sidebar"
  >
    <div class="h-full overflow-y-auto border-r border-gray-200 px-3 py-4 dark:border-gray-700">
      <ul class="space-y-3">
        <li v-for="item in visibleItems" :key="item.id">
          <RouterLink
            :to="item.path"
            :class="[
              'group relative flex h-10 w-full items-center rounded-lg p-2 text-base font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700',
              isActive(item.path) ? 'bg-gray-100 dark:bg-gray-700' : ''
            ]"
          >
            <span class="flex h-6 w-6 shrink-0 items-center justify-center">
              <component
                :is="item.icon"
                :class="[item.iconSizeClass ?? 'h-6 w-6', iconColorClass(item.path)]"
                :stroke-width="item.iconStrokeWidth ?? 1.8"
                aria-hidden="true"
              />
            </span>
            <span class="ml-3 flex-1 whitespace-nowrap text-left" data-sidebar-collapse-hide>{{ item.label }}</span>
            <span
              v-if="item.badge === 'cron-alerts' && hasMisconfiguredCrons"
              class="absolute -top-1 -right-1 inline-flex items-center justify-center w-4 h-4 text-xs font-bold leading-none text-white bg-red-600 rounded-full dark:bg-red-500"
            >!</span>
          </RouterLink>
        </li>
      </ul>
    </div>
  </aside>
</template>
