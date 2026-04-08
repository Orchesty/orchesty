<script setup lang="ts">
import { ref, onMounted } from 'vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import TextInput from '@/components/ui/datagrid/TextInput.vue'
import InviteUserModal from '@/components/users/InviteUserModal.vue'
import { fetchUsers } from '@/services/usersService'
import { fetchUserGroups, fetchPresets } from '@/services/groupsService'
import type { PresetDefinition } from '@/services/groupsService'
import type { User, Group } from '@/types/users'
import type { TableColumn } from '@/types/dashboard'
import { useDataGrid } from '@/composables/useDataGrid'
import { useDateFormat } from '@/composables/useDateFormat'

const props = withDefaults(defineProps<{
  hideInviteButton?: boolean
}>(), {
  hideInviteButton: false,
})

const { formatDateTime } = useDateFormat()

const users = ref<User[]>([])
const userGroupsMap = ref<Record<string, Group[]>>({})
const presetDefs = ref<PresetDefinition[]>([])
const searchFilter = ref('')
const inviteModalOpen = ref(false)
const editModalOpen = ref(false)
const selectedUser = ref<User | null>(null)

const columns: TableColumn[] = [
  { key: 'email', label: 'Email', sortable: true },
  { key: 'role', label: 'Role', sortable: false },
  { key: 'groups', label: 'Groups', sortable: false },
  { key: 'created', label: 'Created', sortable: true },
  { key: 'actions', label: '', sortable: false, className: 'text-right' },
]

function getUserRole(userId: string): PresetDefinition | null {
  const groups = userGroupsMap.value[userId]
  if (!groups) return null
  const presetGroup = groups.find((g) => g.preset != null)
  if (!presetGroup) return null
  return presetDefs.value.find((p) => p.name === presetGroup.preset) ?? null
}

function getUserAccessGroups(userId: string): Group[] {
  const groups = userGroupsMap.value[userId]
  if (!groups) return []
  return groups.filter((g) => g.preset == null)
}

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
    const [response, presets] = await Promise.all([
      fetchUsers({
        page: currentPage.value,
        limit: itemsPerPage.value,
        sort: sortField.value,
        order: sortDirection.value,
        search: searchFilter.value || undefined,
      }),
      presetDefs.value.length === 0 ? fetchPresets() : Promise.resolve(presetDefs.value),
    ])

    users.value = response.data
    totalPages.value = response.meta.totalPages
    totalItems.value = response.meta.total
    presetDefs.value = presets

    loadAllUserGroups(response.data)
  } catch (error) {
    console.error('Failed to load users:', error)
    users.value = []
  } finally {
    loading.value = false
  }
}

async function loadAllUserGroups(userList: User[]) {
  const map: Record<string, Group[]> = {}
  await Promise.all(
    userList.map(async (user) => {
      try {
        const response = await fetchUserGroups(user.id)
        map[user.id] = response.items
      } catch {
        map[user.id] = []
      }
    }),
  )
  userGroupsMap.value = map
}

const handleOpenEdit = (user: User) => {
  selectedUser.value = user
  editModalOpen.value = true
}

const handleUserUpdated = () => {
  loadData()
}

const handleUserRemoved = () => {
  editModalOpen.value = false
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
        <Button v-if="!hideInviteButton" @click="inviteModalOpen = true">
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
          @click="handleOpenEdit(row as User)"
          class="font-medium text-gray-900 whitespace-nowrap dark:text-white hover:underline"
        >
          {{ (row as User).email }}
        </button>
      </template>

      <template #cell-role="{ row }">
        <span
          v-if="getUserRole((row as User).id)"
          class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-300"
        >
          {{ getUserRole((row as User).id)!.label }}
        </span>
        <span v-else class="text-xs text-gray-400 dark:text-gray-500">—</span>
      </template>

      <template #cell-groups="{ row }">
        <div class="flex flex-wrap gap-1">
          <template v-if="getUserAccessGroups((row as User).id).length">
            <span
              v-for="group in getUserAccessGroups((row as User).id)"
              :key="group.id"
              class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300"
            >
              {{ group.name }}
            </span>
          </template>
          <span v-else class="text-xs text-gray-400 dark:text-gray-500">—</span>
        </div>
      </template>

      <template #cell-created="{ row }">
        <span class="whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
          {{ formatDateTime((row as User).created) }}
        </span>
      </template>

      <template #cell-actions="{ row }">
        <button
          @click="handleOpenEdit(row as User)"
          class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-hidden dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
          title="Edit user"
        >
          <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
          </svg>
          <span class="sr-only">Edit user</span>
        </button>
      </template>
    </DataGrid>
  </Card>

  <!-- Invite modal -->
  <InviteUserModal
    v-if="!hideInviteButton"
    v-model="inviteModalOpen"
    @user-invited="handleUserInvited"
  />

  <!-- Edit modal -->
  <InviteUserModal
    v-model="editModalOpen"
    :user="selectedUser"
    @user-updated="handleUserUpdated"
    @user-removed="handleUserRemoved"
  />
</template>
