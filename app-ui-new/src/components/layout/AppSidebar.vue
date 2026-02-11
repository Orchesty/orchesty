<script setup lang="ts">
import { RouterLink, useRoute } from 'vue-router'
import { Bot, ChartPie, Clock, OctagonAlert, List, Workflow, Grip, Settings } from 'lucide-vue-next'
import { useSidebar } from '@/composables/useSidebar'

const route = useRoute()

const isActive = (path: string) => {
  return route.path.startsWith(path)
}

const iconClass = (path: string) => {
  return isActive(path)
    ? 'h-6 w-6 shrink-0 text-primary-600 dark:text-primary-500'
    : 'h-6 w-6 shrink-0 text-gray-400 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white'
}

// Initialize sidebar hover functionality
useSidebar()
</script>

<template>
  <aside
    id="orchesty-sidebar"
    class="h-full w-16 bg-white transition-width duration-75 dark:bg-gray-800"
    aria-label="Sidebar"
  >
    <div class="h-full overflow-y-auto border-r border-gray-200 px-3 py-4 dark:border-gray-700">
      <ul class="space-y-3">
        <!-- Control Center -->
        <li>
          <RouterLink
            to="/dashboard"
            :class="[
              'group flex h-10 w-full items-center rounded-lg p-2 text-base font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700',
              isActive('/dashboard') ? 'bg-gray-100 dark:bg-gray-700' : ''
            ]"
          >
            <ChartPie
              :class="iconClass('/dashboard')"
              aria-hidden="true"
            />
            <span class="ml-3 flex-1 whitespace-nowrap text-left" data-sidebar-collapse-hide
            >Control Center</span
          >
          </RouterLink>
        </li>

        <!-- Trace -->
        <li>
          <RouterLink
            to="/trace"
            :class="[
              'group flex h-10 w-full items-center rounded-lg p-1.5 text-base font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700',
              isActive('/trace') ? 'bg-gray-100 dark:bg-gray-700' : ''
            ]"
          >
            <Bot
              :class="[
                isActive('/trace')
                  ? 'text-primary-600 dark:text-primary-500'
                  : 'text-gray-400 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white',
                'h-7 w-7 shrink-0'
              ]"
              :stroke-width="1.8"
              aria-hidden="true"
            />
            <span class="ml-3 flex-1 whitespace-nowrap text-left" data-sidebar-collapse-hide>Trace</span>
          </RouterLink>
        </li>

        <!-- Scheduled Tasks -->
        <li>
          <RouterLink
            to="/scheduled-tasks"
            :class="[
              'group relative flex h-10 w-full items-center rounded-lg p-2 text-base font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700',
              isActive('/scheduled-tasks') ? 'bg-gray-100 dark:bg-gray-700' : ''
            ]"
          >
            <Clock
              :class="iconClass('/scheduled-tasks')"
              aria-hidden="true"
            />
            <span class="ml-3 flex-1 whitespace-nowrap text-left" data-sidebar-collapse-hide
              >Scheduled Tasks</span
            >
            <span
              class="absolute -top-1 -right-2 inline-flex items-center justify-center w-4 h-4 text-xs font-bold leading-none text-white bg-red-600 rounded-full dark:bg-red-500 hidden"
              data-sidebar-collapse-show
              >!</span
            >
          </RouterLink>
        </li>

        <!-- Failed Messages -->
        <li>
          <RouterLink
            to="/trash"
            :class="[
              'group flex h-10 w-full items-center rounded-lg p-2 text-base font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700',
              isActive('/trash') ? 'bg-gray-100 dark:bg-gray-700' : ''
            ]"
          >
            <OctagonAlert
              :class="iconClass('/trash')"
              aria-hidden="true"
            />
            <span class="ml-3 flex-1 whitespace-nowrap text-left" data-sidebar-collapse-hide
            >Failed Messages</span
          >
          </RouterLink>
        </li>

        <!-- Logs -->
        <li>
          <RouterLink
            to="/logs"
            :class="[
              'group flex h-10 w-full items-center rounded-lg p-2 text-base font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700',
              isActive('/logs') ? 'bg-gray-100 dark:bg-gray-700' : ''
            ]"
          >
            <List
              :class="iconClass('/logs')"
              aria-hidden="true"
            />
            <span class="ml-3 flex-1 whitespace-nowrap text-left" data-sidebar-collapse-hide>Logs</span>
          </RouterLink>
        </li>

        <!-- Topologies -->
        <li>
          <RouterLink
            to="/topologies"
            :class="[
              'group flex h-10 w-full items-center rounded-lg p-2 text-base font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700',
              isActive('/topologies') ? 'bg-gray-100 dark:bg-gray-700' : ''
            ]"
          >
            <Workflow
              :class="iconClass('/topologies')"
              aria-hidden="true"
            />
            <span class="ml-3 flex-1 whitespace-nowrap text-left" data-sidebar-collapse-hide
            >Topologies</span
          >
          </RouterLink>
        </li>

        <!-- Applications -->
        <li>
          <RouterLink
            to="/applications"
            :class="[
              'group flex h-10 w-full items-center rounded-lg p-2 text-base font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700',
              isActive('/applications') ? 'bg-gray-100 dark:bg-gray-700' : ''
            ]"
          >
            <Grip
              :class="iconClass('/applications')"
              aria-hidden="true"
            />
            <span class="ml-3 flex-1 whitespace-nowrap text-left" data-sidebar-collapse-hide
            >Applications</span
          >
          </RouterLink>
        </li>

        <!-- Settings -->
        <li>
          <RouterLink
            to="/settings"
            :class="[
              'group flex h-10 w-full items-center rounded-lg p-2 text-base font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700',
              isActive('/settings') ? 'bg-gray-100 dark:bg-gray-700' : ''
            ]"
          >
            <Settings
              :class="iconClass('/settings')"
              aria-hidden="true"
            />
            <span class="ml-3 flex-1 whitespace-nowrap text-left" data-sidebar-collapse-hide
            >Settings</span
          >
          </RouterLink>
        </li>
      </ul>
    </div>
  </aside>
</template>

