<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { onMounted } from 'vue'
import { useDarkMode } from '@/composables/useDarkMode'

// Initialize dark mode toggle
useDarkMode()

onMounted(() => {
  // Reinitialize Flowbite components after Vue mount
  if (typeof window !== 'undefined' && (window as any).initFlowbite) {
    ;(window as any).initFlowbite()
  }
})
</script>

<template>
  <header class="flex flex-col antialiased">
    <nav
      class="border-b border-gray-200 bg-white px-4 py-2.5 dark:border-gray-700 dark:bg-gray-800 lg:px-6"
    >
      <div class="flex w-full items-center justify-between">
        <!-- Logo -->
        <div class="flex flex-shrink-0 items-center justify-start">
          <RouterLink to="/dashboard" class="mr-6 flex text-black dark:text-gray-300">
            <!-- TODO: Přidat Orchesty logo SVG -->
            <span class="mr-3 h-5 w-auto text-xl font-semibold">Orchesty</span>
          </RouterLink>
        </div>

        <!-- Right side buttons -->
        <div class="flex flex-shrink-0 items-center justify-end">
          <!-- Trace Drawer Toggle Button -->
          <button
            type="button"
            data-drawer-target="traceDrawer"
            data-drawer-toggle="traceDrawer"
            data-drawer-placement="right"
            data-drawer-backdrop="false"
            aria-controls="traceDrawer"
            class="mx-2 items-center rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
          >
            <span class="sr-only">Toggle Trace</span>
            <svg
              class="h-5 w-5 rotate-90 rtl:-rotate-90"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              fill="currentColor"
              viewBox="0 0 18 20"
            >
              <path
                d="m17.914 18.594-8-18a1 1 0 0 0-1.828 0l-8 18a1 1 0 0 0 1.157 1.376L8 18.281V9a1 1 0 0 1 2 0v9.281l6.758 1.689a1 1 0 0 0 1.156-1.376Z"
              ></path>
            </svg>
          </button>

          <!-- User Menu Button -->
          <button
            type="button"
            class="mx-3 flex rounded-full bg-gray-800 text-sm focus:outline-none md:mr-0"
            id="user-menu-button"
            aria-expanded="false"
            data-dropdown-toggle="dropdown"
          >
            <span class="sr-only">Open user menu</span>
            <svg
              class="h-8 w-8 cursor-pointer text-gray-800 dark:text-gray-500 dark:hover:!text-white"
              aria-hidden="true"
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              fill="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                fill-rule="evenodd"
                d="M12 20a7.966 7.966 0 0 1-5.002-1.756l.002.001v-.683c0-1.794 1.492-3.25 3.333-3.25h3.334c1.84 0 3.333 1.456 3.333 3.25v.683A7.966 7.966 0 0 1 12 20ZM2 12C2 6.477 6.477 2 12 2s10 4.477 10 10c0 5.5-4.44 9.963-9.932 10h-.138C6.438 21.962 2 17.5 2 12Zm10-5c-1.84 0-3.333 1.455-3.333 3.25S10.159 13.5 12 13.5c1.84 0 3.333-1.455 3.333-3.25S13.841 7 12 7Z"
                clip-rule="evenodd"
              />
            </svg>
          </button>

          <!-- Dropdown menu -->
          <div
            class="z-50 my-4 hidden w-56 list-none divide-y divide-gray-100 rounded bg-white text-base shadow dark:divide-gray-600 dark:bg-gray-700"
            id="dropdown"
          >
            <div class="px-4 py-3">
              <span class="block text-sm font-semibold text-gray-900 dark:text-white"
                >Neil Sims</span
              >
              <span class="block truncate text-sm text-gray-500 dark:text-gray-400"
                >name@flowbite.com</span
              >
            </div>
            <ul class="py-1 text-gray-500 dark:text-gray-400" aria-labelledby="dropdown">
              <li>
                <RouterLink
                  to="/orchesty/account"
                  class="block px-4 py-2 text-sm hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                >
                  Account settings
                </RouterLink>
              </li>
              <li>
                <RouterLink
                  to="/orchesty/users"
                  class="block px-4 py-2 text-sm hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                >
                  Users
                </RouterLink>
              </li>
              <li>
                <RouterLink
                  to="/orchesty/audit-logs"
                  class="block px-4 py-2 text-sm hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
                >
                  Audit logs
                </RouterLink>
              </li>
            </ul>
            <ul class="py-1 text-start text-sm font-medium text-gray-500 dark:text-gray-400">
              <li>
                <label
                  class="group flex cursor-pointer items-center justify-between gap-2 px-4 py-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-600 dark:hover:text-white"
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
              </li>
            </ul>
            <ul class="py-1 text-gray-500 dark:text-gray-400" aria-labelledby="dropdown">
              <li>
                <RouterLink
                  to="/sign-in"
                  class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                >
                  <svg
                    class="h-4 w-4"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M20 12H8m12 0-4 4m4-4-4-4M9 4H7a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h2"
                    ></path>
                  </svg>
                  Sign out
                </RouterLink>
              </li>
            </ul>
          </div>

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
