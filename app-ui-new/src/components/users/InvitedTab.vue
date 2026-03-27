<script setup lang="ts">
import { ref, onMounted } from 'vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import TextInput from '@/components/ui/datagrid/TextInput.vue'
import Confirm from '@/components/ui/Confirm.vue'
import Modal from '@/components/ui/Modal.vue'
import MoreActions from '@/components/ui/MoreActions.vue'
import InviteUserModal from '@/components/users/InviteUserModal.vue'
import { fetchInvitedUsers, regenerateInvite, deleteInvitedUser } from '@/services/usersService'
import type { InvitedUser } from '@/types/users'
import type { MoreActionsSection } from '@/components/ui/MoreActions.vue'
import type { TableColumn } from '@/types/dashboard'
import { useDataGrid } from '@/composables/useDataGrid'
import { useDateFormat } from '@/composables/useDateFormat'
import { useToast } from '@/composables/useToast'

const { formatDateTime } = useDateFormat()
const { showToast } = useToast()

const users = ref<InvitedUser[]>([])
const searchFilter = ref('')
const modalOpen = ref(false)
const confirmDeleteOpen = ref(false)
const linkModalOpen = ref(false)
const selectedUser = ref<InvitedUser | null>(null)
const generatedLink = ref('')
const copied = ref(false)

const columns: TableColumn[] = [
  { key: 'email', label: 'Email', sortable: true },
  { key: 'created', label: 'Invited', sortable: true },
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
    const response = await fetchInvitedUsers({
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
    console.error('Failed to load invited users:', error)
    users.value = []
  } finally {
    loading.value = false
  }
}

function getInviteLink(hash: string): string {
  return `${window.location.origin}/accept-invite/${hash}`
}

function getActionsForRow(user: InvitedUser): MoreActionsSection[] {
  return [
    {
      items: [
        {
          type: 'button',
          label: 'Regenerate link',
          onClick: () => handleRegenerate(user),
        },
        {
          type: 'button',
          label: 'Delete',
          class: 'text-red-600 dark:text-red-400',
          onClick: () => {
            selectedUser.value = user
            confirmDeleteOpen.value = true
          },
        },
      ],
    },
  ]
}

async function handleRegenerate(user: InvitedUser) {
  try {
    const result = await regenerateInvite(user.id)
    generatedLink.value = getInviteLink(result.hash)
    selectedUser.value = user
    linkModalOpen.value = true
  } catch (error) {
    console.error('Failed to regenerate invite:', error)
    showToast('Failed to regenerate invite link', 'error')
  }
}

async function handleConfirmDelete() {
  if (!selectedUser.value) return
  try {
    await deleteInvitedUser(selectedUser.value.id)
    confirmDeleteOpen.value = false
    selectedUser.value = null
    showToast('Invitation deleted', 'success')
    loadData()
  } catch {
    confirmDeleteOpen.value = false
    selectedUser.value = null
    showToast('Invitation may have already been accepted or deleted', 'warning')
    loadData()
  }
}

async function copyLink() {
  try {
    await navigator.clipboard.writeText(generatedLink.value)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  } catch {
    showToast('Failed to copy link', 'error')
  }
}

function handleCloseLinkModal() {
  linkModalOpen.value = false
  generatedLink.value = ''
  copied.value = false
}

const handleUserInvited = () => {
  loadData()
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <Card>
    <div class="mb-3">
      <div class="flex items-center justify-between mb-2">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Invited users</h3>
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
        <span class="font-medium text-gray-900 whitespace-nowrap dark:text-white">
          {{ (row as InvitedUser).email }}
        </span>
      </template>

      <template #cell-created="{ row }">
        <span class="whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
          {{ formatDateTime((row as InvitedUser).created) }}
        </span>
      </template>

      <template #cell-actions="{ row }">
        <MoreActions
          :id="`invited-actions-${(row as InvitedUser).id}`"
          :sections="getActionsForRow(row as InvitedUser)"
        />
      </template>
    </DataGrid>
  </Card>

  <InviteUserModal
    v-model="modalOpen"
    @user-invited="handleUserInvited"
  />

  <Confirm
    v-if="selectedUser"
    v-model="confirmDeleteOpen"
    id="confirm-delete-invite-modal"
    confirm-text="Yes, delete"
    cancel-text="Cancel"
    @confirm="handleConfirmDelete"
    @cancel="confirmDeleteOpen = false"
  >
    <p class="text-sm text-gray-700 dark:text-gray-300">
      Are you sure you want to delete the invitation for <strong>{{ selectedUser.email }}</strong>?
    </p>
  </Confirm>

  <Modal
    :model-value="linkModalOpen"
    id="regenerated-link-modal"
    title="Invite link"
    size="md"
    @update:model-value="handleCloseLinkModal"
  >
    <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
      A new invite link has been generated. Share it with the user.
    </p>

    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
      <div v-if="selectedUser" class="mb-2 text-sm font-medium text-gray-900 dark:text-white">
        {{ selectedUser.email }}
      </div>
      <div class="flex items-center gap-2">
        <input
          type="text"
          readonly
          :value="generatedLink"
          class="flex-1 rounded-md border border-gray-300 bg-gray-50 px-2.5 py-1.5 text-xs font-mono text-gray-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
          @click="($event.target as HTMLInputElement).select()"
        />
        <button
          type="button"
          @click="copyLink"
          class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
        >
          <svg v-if="copied" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5 text-primary-500">
            <polyline points="20 6 9 17 4 12" />
          </svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
            <rect width="14" height="14" x="8" y="8" rx="2" ry="2" /><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
          </svg>
          {{ copied ? 'Copied' : 'Copy' }}
        </button>
      </div>
    </div>

    <template #footer-actions>
      <Button variant="primary" @click="handleCloseLinkModal">
        Done
      </Button>
    </template>
  </Modal>
</template>
