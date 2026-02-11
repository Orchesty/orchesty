<script setup lang="ts">
import { ref, onMounted } from 'vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import TextInput from '@/components/ui/datagrid/TextInput.vue'
import QuickFilter from '@/components/ui/datagrid/QuickFilter.vue'
import UserDetailDrawer from '@/components/users/UserDetailDrawer.vue'
import InviteUserModal from '@/components/users/InviteUserModal.vue'
import { fetchUsers } from '@/services/usersService'
import type { User, UserStatus } from '@/types/users'
import type { TableColumn } from '@/types/dashboard'
import { useDataGrid } from '@/composables/useDataGrid'

const users = ref<User[]>([])
const statusFilter = ref<UserStatus | ''>('')
const searchFilter = ref('')
const drawerOpen = ref(false)
const modalOpen = ref(false)
const selectedUser = ref<User | null>(null)

const statusOptions = [
  { value: '', label: 'All' },
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' }
]

const columns: TableColumn[] = [
  { key: 'name', label: 'Name', sortable: true },
  { key: 'email', label: 'Email', sortable: true },
  { key: 'role', label: 'Role', sortable: true },
  { key: 'status', label: 'Status', sortable: false },
  { key: 'actions', label: '', sortable: false, className: 'text-right' }
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
  handleSort
} = useDataGrid({
  onDataLoad: async () => {
    loading.value = true
    try {
      const response = await fetchUsers({
        page: currentPage.value,
        limit: itemsPerPage.value,
        sort: sortField.value,
        order: sortDirection.value,
        status: statusFilter.value || undefined,
        search: searchFilter.value || undefined
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
  },
  filters: [statusFilter, searchFilter]
})

const loadData = async () => {
  loading.value = true
  try {
    const response = await fetchUsers({
      page: currentPage.value,
      limit: itemsPerPage.value,
      sort: sortField.value,
      order: sortDirection.value,
      status: statusFilter.value || undefined,
      search: searchFilter.value || undefined
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

const handleUsersInvited = () => {
  modalOpen.value = false
  // Optionally reload data
}

const getStatusClass = (status: UserStatus) => {
  return status === 'active'
    ? 'inline-flex items-center px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full dark:bg-green-900 dark:text-green-300'
    : 'inline-flex items-center px-2 py-1 text-xs font-medium text-gray-800 bg-gray-100 rounded-full dark:bg-gray-700 dark:text-gray-300'
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <Card>
    <div class="mb-3">
      <!-- Title and Add Button -->
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
      <template #quick-filters>
        <QuickFilter
          v-model="statusFilter"
          :options="statusOptions"
          name="users-status-filter"
          label="Show only:"
        />
      </template>

      <template #filters>
        <TextInput
          v-model="searchFilter"
          placeholder="Search for name or email"
          width="w-80"
        />
      </template>

      <!-- Custom cell templates -->
      <template #cell-name="{ row }">
        <button
          @click="handleOpenDrawer(row as User)"
          class="font-medium text-gray-900 whitespace-nowrap dark:text-white hover:underline"
        >
          {{ (row as User).name }}
        </button>
      </template>

      <template #cell-email="{ row }">
        <span class="whitespace-nowrap">{{ (row as User).email }}</span>
      </template>

      <template #cell-role="{ row }">
        <span class="whitespace-nowrap">{{ (row as User).role }}</span>
      </template>

      <template #cell-status="{ row }">
        <span :class="getStatusClass((row as User).status)" class="whitespace-nowrap">
          {{ (row as User).status === 'active' ? 'Active' : 'Inactive' }}
        </span>
      </template>

      <template #cell-actions="{ row }">
        <button
          @click="handleOpenDrawer(row as User)"
          class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
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

  <!-- User Detail Drawer -->
  <UserDetailDrawer
    v-model="drawerOpen"
    :user="selectedUser"
    @user-updated="handleUserUpdated"
    @user-removed="handleUserRemoved"
  />

  <!-- Invite User Modal -->
  <InviteUserModal
    v-model="modalOpen"
    @users-invited="handleUsersInvited"
  />
</template>

