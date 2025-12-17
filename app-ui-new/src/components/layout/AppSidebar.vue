<script setup lang="ts">
import { RouterLink, useRoute } from 'vue-router'
import { useSidebar } from '@/composables/useSidebar'

const route = useRoute()

const isActive = (path: string) => {
  return route.path.startsWith(path)
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
            <svg
              class="h-6 w-6 shrink-0 text-gray-400 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path d="M13.5 2c-.178 0-.356.013-.492.022l-.074.005a1 1 0 0 0-.934.998V11a1 1 0 0 0 1 1h7.975a1 1 0 0 0 .998-.934l.005-.074A7.04 7.04 0 0 0 22 10.5 8.5 8.5 0 0 0 13.5 2Z" />
              <path d="M11 6.025a1 1 0 0 0-1.065-.998 8.5 8.5 0 1 0 9.038 9.039A1 1 0 0 0 17.975 13H11V6.025Z" />
            </svg>
            <span class="ml-3 flex-1 whitespace-nowrap text-left" data-sidebar-collapse-hide
            >Control Center</span
          >
          </RouterLink>
        </li>

        <!-- Trace -->
        <li>
          <RouterLink
            to="/ai-assistant"
            :class="[
              'group flex h-10 w-full items-center rounded-lg p-2 text-base font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700',
              isActive('/ai-assistant') ? 'bg-gray-100 dark:bg-gray-700' : ''
            ]"
          >
            <svg
              class="mx-auto h-5 w-5 shrink-0 rotate-90 text-gray-400 group-hover:text-gray-900 rtl:-rotate-90 dark:text-gray-400 dark:group-hover:text-white"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="currentColor"
              viewBox="0 0 18 20"
            >
              <path d="m17.914 18.594-8-18a1 1 0 0 0-1.828 0l-8 18a1 1 0 0 0 1.157 1.376L8 18.281V9a1 1 0 0 1 2 0v9.281l6.758 1.689a1 1 0 0 0 1.156-1.376Z"></path>
            </svg>
            <span class="ml-3 flex-1 whitespace-nowrap text-left" data-sidebar-collapse-hide>Trace</span>
          </RouterLink>
        </li>

        <!-- Scheduled Tasks -->
        <li>
          <RouterLink
            to="/scheduled-tasks"
            :class="[
              'group relative flex h-10 w-full items-center rounded-lg p-1 text-base font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700',
              isActive('/scheduled-tasks') ? 'bg-gray-100 dark:bg-gray-700' : ''
            ]"
          >
            <svg
              class="mx-auto h-7 w-7 shrink-0 text-gray-400 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              height="24px"
              viewBox="0 -960 960 960"
              width="24px"
              fill="currentColor"
            >
              <path d="m612-292 56-56-148-148v-184h-80v216l172 172ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-400Zm0 320q133 0 226.5-93.5T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 133 93.5 226.5T480-160Z"/>
            </svg>
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
              'group flex h-10 w-full items-center rounded-lg p-1 text-base font-medium text-gray-900 hover:bg-gray-100 dark:text-white dark:hover:bg-gray-700',
              isActive('/trash') ? 'bg-gray-100 dark:bg-gray-700' : ''
            ]"
          >
            <svg
              class="mx-auto h-7 w-7 shrink-0 text-gray-400 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              height="24px"
              viewBox="0 -960 960 960"
              width="24px"
              fill="currentColor"
            >
              <path d="M480-281q14 0 24.5-10.5T515-316q0-14-10.5-24.5T480-351q-14 0-24.5 10.5T445-316q0 14 10.5 24.5T480-281Zm-30-144h60v-263h-60v263ZM330-120 120-330v-300l210-210h300l210 210v300L630-120H330Zm25-60h250l175-175v-250L605-780H355L180-605v250l175 175Zm125-300Z"/>
            </svg>
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
            <svg
              class="h-6 w-6 shrink-0 text-gray-400 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"
              aria-hidden="true"
              height="24px"
              viewBox="0 -960 960 960"
              width="24px"
              fill="currentColor"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path d="M80-160v-160h160v160H80Zm240 0v-160h560v160H320ZM80-400v-160h160v160H80Zm240 0v-160h560v160H320ZM80-640v-160h160v160H80Zm240 0v-160h560v160H320Z"/>
            </svg>
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
            <svg
              class="h-6 w-6 shrink-0 text-gray-400 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"
              aria-hidden="true"
              height="24px"
              viewBox="0 -960 960 960"
              width="24px"
              fill="currentColor"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path d="M600-120v-120H440v-400h-80v120H80v-320h280v120h240v-120h280v320H600v-120h-80v320h80v-120h280v320H600Z"/>
            </svg>
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
            <svg
              class="h-6 w-6 shrink-0 text-gray-400 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"
              aria-hidden="true"
              height="24px"
              viewBox="0 -960 960 960"
              width="24px"
              fill="currentColor"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path d="M240-160q-33 0-56.5-23.5T160-240q0-33 23.5-56.5T240-320q33 0 56.5 23.5T320-240q0 33-23.5 56.5T240-160Zm240 0q-33 0-56.5-23.5T400-240q0-33 23.5-56.5T480-320q33 0 56.5 23.5T560-240q0 33-23.5 56.5T480-160Zm240 0q-33 0-56.5-23.5T640-240q0-33 23.5-56.5T720-320q33 0 56.5 23.5T800-240q0 33-23.5 56.5T720-160ZM240-400q-33 0-56.5-23.5T160-480q0-33 23.5-56.5T240-560q33 0 56.5 23.5T320-480q0 33-23.5 56.5T240-400Zm240 0q-33 0-56.5-23.5T400-480q0-33 23.5-56.5T480-560q33 0 56.5 23.5T560-480q0 33-23.5 56.5T480-400Zm240 0q-33 0-56.5-23.5T640-480q0-33 23.5-56.5T720-560q33 0 56.5 23.5T800-480q0 33-23.5 56.5T720-400ZM240-640q-33 0-56.5-23.5T160-720q0-33 23.5-56.5T240-800q33 0 56.5 23.5T320-720q0 33-23.5 56.5T240-640Zm240 0q-33 0-56.5-23.5T400-720q0-33 23.5-56.5T480-800q33 0 56.5 23.5T560-720q0 33-23.5 56.5T480-640Zm240 0q-33 0-56.5-23.5T640-720q0-33 23.5-56.5T720-800q33 0 56.5 23.5T800-720q0 33-23.5 56.5T720-640Z"/>
            </svg>
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
            <svg
              class="h-6 w-6 shrink-0 text-gray-400 group-hover:text-gray-900 dark:text-gray-400 dark:group-hover:text-white"
              aria-hidden="true"
              height="24px"
              viewBox="0 -960 960 960"
              width="24px"
              fill="currentColor"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path d="m234-480-12-60q-12-5-22.5-10.5T178-564l-58 18-40-68 46-40q-2-13-2-26t2-26l-46-40 40-68 58 18q11-8 21.5-13.5T222-820l12-60h80l12 60q12 5 22.5 10.5T370-796l58-18 40 68-46 40q2 13 2 26t-2 26l46 40-40 68-58-18q-11 8-21.5 13.5T326-540l-12 60h-80Zm40-120q33 0 56.5-23.5T354-680q0-33-23.5-56.5T274-760q-33 0-56.5 23.5T194-680q0 33 23.5 56.5T274-600ZM592-40l-18-84q-17-6-31.5-14.5T514-158l-80 26-56-96 64-56q-2-18-2-36t2-36l-64-56 56-96 80 26q14-11 28.5-19.5T574-516l18-84h112l18 84q17 6 31.5 14.5T782-482l80-26 56 96-64 56q2 18 2 36t-2 36l64 56-56 96-80-26q-14 11-28.5 19.5T722-124l-18 84H592Zm56-160q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35Z"/>
            </svg>
            <span class="ml-3 flex-1 whitespace-nowrap text-left" data-sidebar-collapse-hide
            >Settings</span
          >
          </RouterLink>
        </li>
      </ul>
    </div>
  </aside>
</template>

