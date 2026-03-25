<script setup lang="ts">
import { ref, onMounted } from 'vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import TextInput from '@/components/ui/datagrid/TextInput.vue'
import GroupDetailDrawer from '@/components/users/GroupDetailDrawer.vue'
import CreateGroupModal from '@/components/users/CreateGroupModal.vue'
import { fetchGroups } from '@/services/groupsService'
import type { Group } from '@/types/users'
import type { TableColumn } from '@/types/dashboard'
import { useDataGrid } from '@/composables/useDataGrid'

const groups = ref<Group[]>([])
const searchFilter = ref('')
const drawerOpen = ref(false)
const modalOpen = ref(false)
const selectedGroup = ref<Group | null>(null)

const columns: TableColumn[] = [
  { key: 'name', label: 'Group name', sortable: true },
  { key: 'modules', label: 'Modules', sortable: false },
  { key: 'users', label: 'Users', sortable: false },
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
      const response = await fetchGroups({
        page: currentPage.value,
        limit: itemsPerPage.value,
        sort: sortField.value,
        order: sortDirection.value,
        search: searchFilter.value || undefined
      })

      groups.value = response.data
      totalPages.value = response.meta.totalPages
      totalItems.value = response.meta.total
    } catch (error) {
      console.error('Failed to load groups:', error)
      groups.value = []
    } finally {
      loading.value = false
    }
  },
  filters: [searchFilter]
})

const loadData = async () => {
  loading.value = true
  try {
    const response = await fetchGroups({
      page: currentPage.value,
      limit: itemsPerPage.value,
      sort: sortField.value,
      order: sortDirection.value,
      search: searchFilter.value || undefined
    })

    groups.value = response.data
    totalPages.value = response.meta.totalPages
    totalItems.value = response.meta.total
  } catch (error) {
    console.error('Failed to load groups:', error)
    groups.value = []
  } finally {
    loading.value = false
  }
}

const handleOpenDrawer = (group: Group) => {
  selectedGroup.value = group
  drawerOpen.value = true
}

const handleGroupUpdated = () => {
  loadData()
}

const handleGroupRemoved = () => {
  drawerOpen.value = false
  loadData()
}

const handleGroupCreated = () => {
  modalOpen.value = false
  loadData()
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
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Groups</h3>
        <Button @click="modalOpen = true">
          + Create group
        </Button>
      </div>
    </div>

    <DataGrid
      :columns="columns"
      :data="groups"
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
          placeholder="Search for group name"
          width="w-80"
        />
      </template>
      <!-- Custom cell templates -->
      <template #cell-name="{ row }">
        <button
          @click="handleOpenDrawer(row as Group)"
          class="font-medium text-gray-900 whitespace-nowrap dark:text-white hover:underline"
        >
          {{ (row as Group).name }}
        </button>
      </template>

      <template #cell-modules="{ row }">
        <span class="whitespace-nowrap">{{ (row as Group).modules.length }}</span>
      </template>

      <template #cell-users="{ row }">
        <span class="whitespace-nowrap">{{ (row as Group).users.length }}</span>
      </template>

      <template #cell-actions="{ row }">
        <button
          @click="handleOpenDrawer(row as Group)"
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

  <!-- Group Detail Drawer -->
  <GroupDetailDrawer
    v-model="drawerOpen"
    :group="selectedGroup"
    @group-updated="handleGroupUpdated"
    @group-removed="handleGroupRemoved"
  />

  <!-- Create Group Modal -->
  <CreateGroupModal
    v-model="modalOpen"
    @group-created="handleGroupCreated"
  />
</template>

