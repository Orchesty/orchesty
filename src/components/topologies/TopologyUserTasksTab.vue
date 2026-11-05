<script setup lang="ts">
import { ref, onActivated, onDeactivated, computed, watch } from 'vue'
import Card from '@/components/ui/Card.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import DropdownFilter from '@/components/ui/datagrid/DropdownFilter.vue'
import Confirm from '@/components/ui/Confirm.vue'
import type { TrashItem, TrashQueryParams } from '@/types/trash'
import type { TableColumn } from '@/types/dashboard'
import {
  fetchTrashItems,
  approveTrashItem,
  rejectTrashItem,
} from '@/services/trashService'
import { useDataGrid } from '@/composables/useDataGrid'
import { useToast } from '@/composables/useToast'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'
import { useDateFormat } from '@/composables/useDateFormat'
import { useTabDataFreshness } from '@/composables/useTabDataFreshness'

interface Props {
  topologyId: string
  topologyName: string
  refreshKey?: number
}

const props = defineProps<Props>()

const { showToast } = useToast()
const { isActive, isStale, markFresh, invalidate } = useTabDataFreshness()
const { getNodeName } = useTopologyNodeMappings()
const { formatDateTime } = useDateFormat()

const items = ref<TrashItem[]>([])
const typeFilter = ref<string | null>(null)
const acceptConfirmOpen = ref(false)
const rejectConfirmOpen = ref(false)
const rowToAct = ref<TrashItem | null>(null)

const typeOptions = [
  { value: null, label: 'All types' },
  { value: 'userTask', label: 'Breakpoint' },
  { value: 'confirm', label: 'Confirm' },
  { value: 'question', label: 'Question' },
]

const columns: TableColumn[] = [
  { key: 'type', label: 'Type', sortable: false },
  { key: 'node', label: 'Node', sortable: false },
  { key: 'question', label: 'Question', sortable: false },
  { key: 'identity', label: 'Identity', sortable: false },
  { key: 'timestamp', label: 'Timestamp', sortable: true },
  { key: 'actions', label: '', className: 'text-right w-24' },
]

const typeLabel = (type?: string) => {
  if (type === 'confirm') return 'Confirm'
  if (type === 'question') return 'Question'
  if (type === 'userTask') return 'Breakpoint'
  return type || '—'
}

const loadData = async () => {
  loading.value = true

  const params: TrashQueryParams = {
    page: currentPage.value,
    perPage: itemsPerPage.value,
    sortBy: sortField.value,
    sortOrder: sortDirection.value,
    topology: props.topologyId,
    types: typeFilter.value ? [typeFilter.value] : ['userTask', 'confirm', 'question'],
  }

  try {
    const response = await fetchTrashItems(params)
    items.value = response.data
    totalItems.value = response.pagination.total
    totalPages.value = response.pagination.totalPages
    markFresh()
  } catch (error) {
    console.error('Failed to load user tasks:', error)
  } finally {
    loading.value = false
  }
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
  defaultSort: { field: 'timestamp', direction: 'desc' },
  onDataLoad: loadData,
  filters: [typeFilter],
})

onActivated(() => {
  isActive.value = true
  if (isStale()) loadData()
})

onDeactivated(() => {
  isActive.value = false
})

watch(() => props.refreshKey, () => {
  invalidate()
  if (isActive.value) loadData()
})

const askAccept = (row: TrashItem) => {
  rowToAct.value = row
  acceptConfirmOpen.value = true
}

const askReject = (row: TrashItem) => {
  rowToAct.value = row
  rejectConfirmOpen.value = true
}

const confirmAccept = async () => {
  if (!rowToAct.value) return
  acceptConfirmOpen.value = false
  try {
    await approveTrashItem(rowToAct.value.id)
    showToast(rowToAct.value.type === 'confirm' ? 'Confirmed Yes' : 'Accepted', 'success')
    loadData()
  } catch (error) {
    console.error('Accept failed:', error)
    showToast('Failed to accept the task', 'error')
  } finally {
    rowToAct.value = null
  }
}

const confirmReject = async () => {
  if (!rowToAct.value) return
  rejectConfirmOpen.value = false
  try {
    await rejectTrashItem(rowToAct.value.id)
    showToast(rowToAct.value.type === 'confirm' ? 'Confirmed No' : rejectedToast(rowToAct.value.type), 'success')
    loadData()
  } catch (error) {
    console.error('Reject failed:', error)
    showToast('Failed to reject the task', 'error')
  } finally {
    rowToAct.value = null
  }
}

// Question is answered in Pulse (free-text reply); here it can only be rejected.
const rejectedToast = (type?: string) => (type === 'question' ? 'Question rejected' : 'Rejected')

const rejectLabel = computed(() =>
  rowToAct.value?.type === 'confirm' ? 'Yes, choose No' : 'Yes, reject',
)

const rejectQuestion = computed(() => {
  if (rowToAct.value?.type === 'confirm') return 'Send this confirmation down the No path?'
  if (rowToAct.value?.type === 'question') return 'Reject this question? The run will not continue.'
  return 'Reject this parked message?'
})

const typeBadgeClass = (type?: string) => {
  if (type === 'confirm') return 'bg-primary-100 text-primary-700 dark:bg-primary-900/40 dark:text-primary-300'
  if (type === 'question') return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200'
  return 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200'
}
</script>

<template>
  <Card>
    <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">User Tasks</h3>
    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
      Live parked messages for this topology. Failed Messages stays the trash.
    </p>

    <DataGrid
      :columns="columns"
      :data="items"
      :loading="loading"
      :current-page="currentPage"
      :total-pages="totalPages"
      :total-items="totalItems"
      :items-per-page="itemsPerPage"
      :sort-field="sortField"
      :sort-direction="sortDirection"
      row-id-key="id"
      show-refresh
      @page-change="handlePageChange"
      @per-page-change="handlePerPageChange"
      @sort="handleSort"
      @refresh="loadData"
    >
      <template #filters>
        <DropdownFilter
          v-model="typeFilter"
          :options="typeOptions"
          placeholder="All types"
        />
      </template>

      <template #cell-type="{ row }">
        <span
          class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
          :class="typeBadgeClass((row as TrashItem).type)"
        >
          {{ typeLabel((row as TrashItem).type) }}
        </span>
      </template>

      <template #cell-node="{ row }">
        <span class="text-sm text-gray-900 dark:text-white">
          {{ getNodeName((row as TrashItem).nodeId) }}
        </span>
      </template>

      <template #cell-question="{ row }">
        {{ (row as TrashItem).question || '—' }}
      </template>

      <template #cell-identity="{ row }">
        {{ (row as TrashItem).identity || '—' }}
      </template>

      <template #cell-timestamp="{ value }">
        {{ formatDateTime(value) }}
      </template>

      <template #cell-actions="{ row }">
        <div class="flex items-center justify-end gap-1">
          <button
            v-if="(row as TrashItem).type !== 'question'"
            type="button"
            class="inline-flex items-center rounded-lg px-2 py-1 text-xs font-medium text-primary-700 hover:bg-primary-50 dark:text-primary-300 dark:hover:bg-primary-900/30"
            @click="askAccept(row as TrashItem)"
          >
            {{ (row as TrashItem).type === 'confirm' ? 'Yes' : 'Accept' }}
          </button>
          <button
            type="button"
            class="inline-flex items-center rounded-lg px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-900/30"
            @click="askReject(row as TrashItem)"
          >
            {{ (row as TrashItem).type === 'confirm' ? 'No' : 'Reject' }}
          </button>
        </div>
      </template>
    </DataGrid>

    <Confirm
      v-model="acceptConfirmOpen"
      id="user-task-accept-confirm"
      confirm-text="Yes, continue"
      cancel-text="Cancel"
      confirm-variant="primary"
      @confirm="confirmAccept"
    >
      <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
        Continue this parked message on the Yes / accept path?
      </h3>
    </Confirm>

    <Confirm
      v-model="rejectConfirmOpen"
      id="user-task-reject-confirm"
      :confirm-text="rejectLabel"
      cancel-text="Cancel"
      @confirm="confirmReject"
    >
      <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
        {{ rejectQuestion }}
      </h3>
    </Confirm>
  </Card>
</template>
