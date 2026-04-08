<script setup lang="ts">
import { RouterLink, useRouter } from 'vue-router'
import { onMounted, computed } from 'vue'
import { Moon, Sun, CircleHelp } from 'lucide-vue-next'
import { useDarkMode } from '@/composables/useDarkMode'
import { useAuthStore } from '@/stores/auth'
import { useAuthorization } from '@/composables/useAuthorization'
import { useHelp } from '@/composables/useHelp'
import DropdownMenu, { type DropdownMenuSection } from '@/components/ui/DropdownMenu.vue'
import HelpDrawer from '@/components/help/HelpDrawer.vue'

interface Props {
  extraMenuItems?: { type: 'link'; label: string; to: string }[]
  extraNavSlots?: string[]
}

const props = withDefaults(defineProps<Props>(), {
  extraMenuItems: () => [],
  extraNavSlots: () => [],
})

const { isDark, toggleDarkMode } = useDarkMode()

const authStore = useAuthStore()
const { can } = useAuthorization()
const { isOpen: helpOpen, contextHelpId, toggle: toggleHelp } = useHelp()
const router = useRouter()

const handleLogout = async () => {
  await authStore.logout()
  router.push('/sign-in')
}

const accountMenuSections = computed<DropdownMenuSection[]>(() => {
  const menuItems: { type: 'link'; label: string; to: string }[] = [
    { type: 'link', label: 'Account settings', to: '/orchesty/account' },
  ]

  if (can('user:read')) {
    menuItems.push({ type: 'link', label: 'Users', to: '/users' })
  }

  menuItems.push(
    ...props.extraMenuItems.map((item) => ({ type: 'link' as const, label: item.label, to: item.to })),
  )

  return [
    {
      header: {
        title: authStore.user?.email.split('@')[0] || 'User',
        subtitle: authStore.user?.email || '',
      },
      items: menuItems,
    },
    {
      items: [
        { type: 'custom', slotName: 'sign-out' },
      ],
    },
  ]
})

onMounted(async () => {
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
        <div class="flex w-16 shrink-0 items-center justify-center">
          <RouterLink to="/dashboard" class="flex items-center">
            <img src="/logo.svg" alt="Orchesty" class="h-8 w-8" />
          </RouterLink>
        </div>

        <div class="flex shrink-0 items-center justify-end">
          <slot name="extra-nav-buttons" />

          <!-- Help toggle -->
          <button
            type="button"
            class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 focus:outline-hidden dark:text-gray-400 dark:hover:bg-gray-700"
            @click="toggleHelp(contextHelpId)"
          >
            <CircleHelp class="h-6 w-6" />
            <span class="sr-only">Toggle help</span>
          </button>

          <!-- Dark mode toggle -->
          <button
            type="button"
            class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 focus:outline-hidden dark:text-gray-400 dark:hover:bg-gray-700"
            @click="toggleDarkMode"
          >
            <Moon v-if="!isDark" class="h-5 w-5" />
            <Sun v-else class="h-5 w-5" />
          </button>

          <DropdownMenu
            id="account-dropdown"
            :sections="accountMenuSections"
          >
            <template #trigger>
              <button class="mx-3 inline-flex items-center focus:outline-hidden md:mr-0">
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

          <button
            type="button"
            id="toggleMobileMenuButton"
            class="items-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white md:ml-2 md:hidden"
          >
            <span class="sr-only">Open menu</span>
            <svg class="h-[18px] w-[18px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h15M1 7h15M1 13h15"></path>
            </svg>
          </button>
        </div>
      </div>
    </nav>
  </header>

  <HelpDrawer v-model="helpOpen" :help-id="contextHelpId" />
</template>
