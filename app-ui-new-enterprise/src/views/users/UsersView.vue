<script setup lang="ts">
import { ref } from 'vue'
import Tabs, { type Tab } from '@/components/ui/Tabs.vue'
import Button from '@/components/ui/Button.vue'
import UsersTab from '@/components/users/UsersTab.vue'
import InvitedTab from '@/components/users/InvitedTab.vue'
import GroupsTab from '@/components/users/GroupsTab.vue'
import AddUserModal from '@/components/users/AddUserModal.vue'
import InviteUserModal from '@/components/users/InviteUserModal.vue'
import { useCloudMode } from '@/composables/useCloudMode'

const { cloudMode } = useCloudMode()
const addUserModalOpen = ref(false)
const inviteModalOpen = ref(false)
const usersTabRef = ref<InstanceType<typeof UsersTab> | null>(null)
const invitedTabRef = ref<InstanceType<typeof InvitedTab> | null>(null)

function handleUserAdded() {
  usersTabRef.value?.loadData()
  invitedTabRef.value?.loadData()
}

function handleUserInvited() {
  usersTabRef.value?.loadData()
  invitedTabRef.value?.loadData()
}

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
  <main class="h-full overflow-y-auto"><div class="px-4 pb-4 pt-6">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Users management</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage users and groups</p>
      </div>
      <Button v-if="cloudMode" @click="addUserModalOpen = true">
        + Add user
      </Button>
      <Button v-else @click="inviteModalOpen = true">
        + Invite user
      </Button>
    </div>

    <!-- Tabs -->
    <Tabs :tabs="usersTabs" content-id="users-management-content" storage-key="users-tab">
      <!-- Users Tab Content -->
      <div id="users-content" role="tabpanel" aria-labelledby="users-tab">
        <UsersTab ref="usersTabRef" :hide-invite-button="true" />
      </div>

      <!-- Invited Tab Content -->
      <div id="invited-content" role="tabpanel" aria-labelledby="invited-tab" class="hidden">
        <InvitedTab ref="invitedTabRef" :hide-invite-button="true" />
      </div>

      <!-- Groups Tab Content -->
      <div id="groups-content" role="tabpanel" aria-labelledby="groups-tab" class="hidden">
        <GroupsTab />
      </div>
    </Tabs>

    <AddUserModal
      v-if="cloudMode"
      v-model="addUserModalOpen"
      :cloud-mode="cloudMode"
      @user-added="handleUserAdded"
    />

    <InviteUserModal
      v-if="!cloudMode"
      v-model="inviteModalOpen"
      @user-invited="handleUserInvited"
    />
  </div></main>
</template>

