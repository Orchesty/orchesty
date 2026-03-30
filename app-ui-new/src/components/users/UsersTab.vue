<script setup lang="ts">
import { ref, onMounted } from 'vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import TextInput from '@/components/ui/datagrid/TextInput.vue'
import UserDetailDrawer from '@/components/users/UserDetailDrawer.vue'
import InviteUserModal from '@/components/users/InviteUserModal.vue'
import { fetchUsers } from '@/services/usersService'
import type { User } from '@/types/users'
import type { TableColumn } from '@/types/dashboard'
import { useDataGrid } from '@/composables/useDataGrid'
import { useDateFormat } from '@/composables/useDateFormat'

const { formatDateTime } = useDateFormat()

const users = ref<User[]>([])
const searchFilter = ref('')
const drawerOpen = ref(false)
const modalOpen = ref(false)
const selectedUser = ref<User | null>(null)

const columns: TableColumn[] = [
  { key: 'email', label: 'Email', sortable: true },
  { key: 'created', label: 'Created', sortable: true },
  { key: 'actions', label: '', sortable: false, className: 'text-right' },
]

const {
  currentPage,
  itemsPerPage,
  totalPages,
  totalItems,
  sortField,
  sortDirection,
  loading,
  handlePageChange,
  handlePerPageChange,
  handleSort,
} = useDataGrid({
  onDataLoad: loadData,
  filters: [searchFilter],
})

async function loadData() {
  loading.value = true
  try {
    const response = await fetchUsers({
      page: currentPage.value,
      limit: itemsPerPage.value,
      sort: sortField.value,
      order: sortDirection.value,
      search: searchFilter.value || undefined,
    })

    users.value = response.data
    totalPages.value = response.meta.totalPages
    totalItems.value = response.meta.total
  } catch (error) {
    console.error('Failed to load users:', error)
    users.value = []
  } finally {
    loading.value = false
  }
}

const handleOpenDrawer = (user: User) => {
  selectedUser.value = user
  drawerOpen.value = true
}

const handleUserUpdated = () => {
  loadData()
}

const handleUserRemoved = () => {
  drawerOpen.value = false
  loadData()
}

const handleUserInvited = () => {
  loadData()
}

onMounted(() => {
  loadData()
})

defineExpose({ loadData })
</script>

<template>
  <Card>
    <div class="mb-3">
      <div class="flex items-center justify-between mb-2">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Users</h3>
        <Button @click="modalOpen = true">
          + Invite user
        </Button>
      </div>
    </div>

    <DataGrid
      :columns="columns"
      :data="users"
      :current-page="currentPage"
      :total-pages="totalPages"
      :total-items="totalItems"
      :items-per-page="itemsPerPage"
      :loading="loading"
      :sort-field="sortField"
      :sort-direction="sortDirection"
      @page-change="handlePageChange"
      @per-page-change="handlePerPageChange"
      @sort="handleSort"
    >
      <template #filters>
        <TextInput
          v-model="searchFilter"
          placeholder="Search by email"
          width="w-80"
        />
      </template>

      <template #cell-email="{ row }">
        <button
          @click="handleOpenDrawer(row as User)"
          class="font-medium text-gray-900 whitespace-nowrap dark:text-white hover:underline"
        >
          {{ (row as User).email }}
        </button>
      </template>

      <template #cell-created="{ row }">
        <span class="whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
          {{ formatDateTime((row as User).created) }}
        </span>
      </template>

      <template #cell-actions="{ row }">
        <button
          @click="handleOpenDrawer(row as User)"
          class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
          title="View details"
        >
          <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
          </svg>
          <span class="sr-only">View details</span>
        </button>
      </template>
    </DataGrid>
  </Card>

  <UserDetailDrawer
    v-model="drawerOpen"
    :user="selectedUser"
    @user-updated="handleUserUpdated"
    @user-removed="handleUserRemoved"
  />

  <InviteUserModal
    v-model="modalOpen"
    @user-invited="handleUserInvited"
  />
</template>
