<script setup lang="ts">
import Tabs, { type Tab } from '@/components/ui/Tabs.vue'
import UsersTab from '@/components/users/UsersTab.vue'
import InvitedTab from '@/components/users/InvitedTab.vue'

const usersTabs: Tab[] = [
  {
    id: 'users',
    label: 'Users',
    target: 'users-content',
    icon: 'M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z',
    iconViewBox: '0 0 20 20'
  },
  {
    id: 'invited',
    label: 'Invited',
    target: 'invited-content',
    icon: 'M16 14h2a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H2a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2m0 0h12M4 14l4-4m8 4-4-4M2 6l8 5 8-5',
    iconViewBox: '0 0 20 16'
  },
]
</script>

<template>
  <main class="h-full overflow-y-auto">
    <div class="px-4 pb-4 pt-6">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Users</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage users and invitations</p>
      </div>

      <Tabs :tabs="usersTabs" content-id="users-management-content">
        <div id="users-content" role="tabpanel" aria-labelledby="users-tab">
          <UsersTab />
        </div>

        <div id="invited-content" role="tabpanel" aria-labelledby="invited-tab" class="hidden">
          <InvitedTab />
        </div>
      </Tabs>
    </div>
  </main>
</template>
