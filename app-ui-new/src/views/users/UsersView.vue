<script setup lang="ts">
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Tabs, { type Tab } from '@/components/ui/Tabs.vue'
import UsersTab from '@/components/users/UsersTab.vue'
import GroupsTab from '@/components/users/GroupsTab.vue'

const usersTabs: Tab[] = [
  { 
    id: 'users', 
    label: 'Users', 
    target: 'users-content',
    icon: 'M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z',
    iconViewBox: '0 0 20 20'
  },
  { 
    id: 'groups', 
    label: 'Groups', 
    target: 'groups-content',
    icon: 'M6.143 0H1.857A1.857 1.857 0 0 0 0 1.857v4.286C0 7.169.831 8 1.857 8h4.286A1.857 1.857 0 0 0 8 6.143V1.857A1.857 1.857 0 0 0 6.143 0Zm10 0h-4.286A1.857 1.857 0 0 0 10 1.857v4.286C10 7.169 10.831 8 11.857 8h4.286A1.857 1.857 0 0 0 18 6.143V1.857A1.857 1.857 0 0 0 16.143 0Zm-10 10H1.857A1.857 1.857 0 0 0 0 11.857v4.286C0 17.169.831 18 1.857 18h4.286A1.857 1.857 0 0 0 8 16.143v-4.286A1.857 1.857 0 0 0 6.143 10Zm10 0h-4.286A1.857 1.857 0 0 0 10 11.857v4.286c0 1.026.831 1.857 1.857 1.857h4.286A1.857 1.857 0 0 0 18 16.143v-4.286A1.857 1.857 0 0 0 16.143 10Z',
    iconViewBox: '0 0 18 18'
  }
]
</script>

<template>
  <DashboardLayout>
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Users management</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage users and groups</p>
    </div>

    <!-- Tabs -->
    <Tabs :tabs="usersTabs" content-id="users-management-content">
      <!-- Users Tab Content -->
      <div id="users-content" role="tabpanel" aria-labelledby="users-tab">
        <UsersTab />
      </div>

      <!-- Groups Tab Content -->
      <div id="groups-content" role="tabpanel" aria-labelledby="groups-tab" class="hidden">
        <GroupsTab />
      </div>
    </Tabs>
  </DashboardLayout>
</template>

