<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import TextInput from '@/components/ui/datagrid/TextInput.vue'
import GroupDetailDrawer from '@/components/users/GroupDetailDrawer.vue'
import CreateGroupModal from '@/components/users/CreateGroupModal.vue'
import { fetchGroups } from '@/services/groupsService'
import type { Group } from '@/types/users'
import type { TableColumn } from '@/types/dashboard'

const allGroups = ref<Group[]>([])
const searchFilter = ref('')
const loading = ref(false)
const drawerOpen = ref(false)
const modalOpen = ref(false)
const selectedGroup = ref<Group | null>(null)

const columns: TableColumn[] = [
  { key: 'name', label: 'Group name', sortable: true },
  { key: 'usersCount', label: 'Users', sortable: true },
  { key: 'actions', label: '', sortable: false, className: 'text-right' },
]

const filteredGroups = computed(() => {
  if (!searchFilter.value) return allGroups.value
  const q = searchFilter.value.toLowerCase()
  return allGroups.value.filter((g) => g.name.toLowerCase().includes(q))
})

const loadData = async () => {
  loading.value = true
  try {
    const response = await fetchGroups()
    allGroups.value = response.items
  } catch (error) {
    console.error('Failed to load groups:', error)
    allGroups.value = []
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

defineExpose({ loadData })

onMounted(() => {
  loadData()
})
</script>

<template>
  <Card>
    <div class="mb-3">
      <div class="flex items-center justify-between mb-2">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Groups</h3>
        <Button @click="modalOpen = true">
          + Create group
        </Button>
      </div>
    </div>

    <DataGrid
      :columns="columns"
      :data="filteredGroups"
      :loading="loading"
      :hide-pagination="true"
    >
      <template #filters>
        <TextInput
          v-model="searchFilter"
          placeholder="Search by group name"
          width="w-80"
        />
      </template>

      <template #cell-name="{ row }">
        <button
          @click="handleOpenDrawer(row as Group)"
          class="font-medium text-gray-900 whitespace-nowrap dark:text-white hover:underline"
        >
          {{ (row as Group).name }}
        </button>
      </template>

      <template #cell-usersCount="{ row }">
        <span class="text-sm text-gray-500 dark:text-gray-400">
          {{ (row as Group).usersCount }}
        </span>
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

  <GroupDetailDrawer
    v-model="drawerOpen"
    :group="selectedGroup"
    @group-updated="handleGroupUpdated"
    @group-removed="handleGroupRemoved"
  />

  <CreateGroupModal
    v-model="modalOpen"
    @group-created="handleGroupCreated"
  />
</template>
