<script setup lang="ts">
import { RouterLink, useRouter } from 'vue-router'
import { onMounted, computed } from 'vue'
import { BotMessageSquare } from 'lucide-vue-next'
import { useDarkMode } from '@/composables/useDarkMode'
import { useTraceDrawer } from '@/composables/useTraceDrawer'
import { useAuthStore } from '@/stores/auth'
import DropdownMenu, { type DropdownMenuSection } from '@/components/ui/DropdownMenu.vue'

// Initialize dark mode toggle
useDarkMode()

// Trace drawer toggle
const { toggleDrawer } = useTraceDrawer()

// Auth store and router
const authStore = useAuthStore()
const router = useRouter()

// Logout handler
const handleLogout = async () => {
  await authStore.logout()
  router.push('/sign-in')
}

// Account dropdown menu sections
const accountMenuSections = computed<DropdownMenuSection[]>(() => [
  {
    header: {
      title: authStore.user?.email.split('@')[0] || 'User',
      subtitle: authStore.user?.email || '',
    },
    items: [
      { type: 'link', label: 'Account settings', to: '/orchesty/account' },
      { type: 'link', label: 'Users', to: '/users' },
      { type: 'link', label: 'Audit logs', to: '/audit-logs' },
    ],
  },
  {
    items: [
      { type: 'custom', slotName: 'dark-mode-toggle' },
    ],
  },
  {
    items: [
      { type: 'custom', slotName: 'sign-out' },
    ],
  },
])

onMounted(async () => {
  // Only init components that use data-* attributes (dropdowns, tabs, collapses).
  // Drawers and modals manage their own Flowbite instances in Vue — calling
  // initDrawers/initModals here would warn about instances that don't exist yet.
  const { initDropdowns, initTabs, initCollapses } = await import('flowbite')
  initDropdowns()
  initTabs()
  initCollapses()
})
</script>

<template>
  <header class="flex flex-col antialiased">
    <nav
      class="relative z-50 border-b border-gray-200 bg-white py-2.5 pr-4 dark:border-gray-700 dark:bg-gray-800 lg:pr-6"
    >
      <div class="flex w-full items-center justify-between">
        <!-- Logo -->
        <div class="flex w-16 flex-shrink-0 items-center justify-center">
          <RouterLink to="/dashboard" class="flex items-center">
            <img src="/logo.svg" alt="Orchesty" class="h-8 w-8" />
          </RouterLink>
        </div>

        <!-- Right side buttons -->
        <div class="flex flex-shrink-0 items-center justify-end">
          <!-- Trace Drawer Toggle Button -->
          <button
            type="button"
            @click="toggleDrawer"
            class="mx-2 inline-flex items-center rounded-lg p-1 text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
          >
            <span class="sr-only">Toggle Trace</span>
            <BotMessageSquare class="h-6 w-6" aria-hidden="true" />
          </button>

          <!-- User Menu -->
          <DropdownMenu
            id="account-dropdown"
            :sections="accountMenuSections"
          >
            <template #trigger>
              <button class="mx-3 inline-flex items-center focus:outline-none md:mr-0">
                <span class="sr-only">Open user menu</span>
                <svg
                  class="h-8 w-8 cursor-pointer text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white"
                  aria-hidden="true"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="currentColor"
                  viewBox="0 -960 960 960"
                >
                  <path
                    d="M234-276q51-39 114-61.5T480-360q69 0 132 22.5T726-276q35-41 54.5-93T800-480q0-133-93.5-226.5T480-800q-133 0-226.5 93.5T160-480q0 59 19.5 111t54.5 93Zm246-164q-59 0-99.5-40.5T340-580q0-59 40.5-99.5T480-720q59 0 99.5 40.5T620-580q0 59-40.5 99.5T480-440Zm0 360q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"
                  />
                </svg>
              </button>
            </template>

            <template #dark-mode-toggle>
              <label
                class="group flex cursor-pointer items-center justify-between gap-2 px-4 py-2 text-sm text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <span>Dark mode</span>
                <div class="ml-auto inline-flex items-center">
                  <input
                    id="theme-toggle"
                    type="checkbox"
                    value=""
                    class="peer sr-only"
                  />
                  <div
                    class="peer relative h-5 w-9 rounded-full bg-gray-200 after:absolute after:start-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-500 dark:bg-gray-600 dark:peer-focus:ring-primary-800 rtl:peer-checked:after:-translate-x-full"
                  ></div>
                  <span class="sr-only">Toggle dark mode</span>
                </div>
              </label>
            </template>

            <template #sign-out>
              <button
                @click="handleLogout"
                class="flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
              >
                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H8m12 0-4 4m4-4-4-4M9 4H7a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h2"></path>
                </svg>
                <span>Sign out</span>
              </button>
            </template>
          </DropdownMenu>

          <!-- Mobile menu toggle button -->
          <button
            type="button"
            id="toggleMobileMenuButton"
            class="items-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white md:ml-2 md:hidden"
          >
            <span class="sr-only">Open menu</span>
            <svg
              class="h-[18px] w-[18px]"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 17 14"
            >
              <path
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M1 1h15M1 7h15M1 13h15"
              ></path>
            </svg>
          </button>
        </div>
      </div>
    </nav>
  </header>
</template>
