<script setup lang="ts">
import { ref, onMounted } from 'vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import TextInput from '@/components/ui/datagrid/TextInput.vue'
import TimeRangeFilterWithCustomRange from '@/components/ui/TimeRangeFilterWithCustomRange.vue'
import AuditLogDetailModal from '@/components/audit-logs/AuditLogDetailModal.vue'
import { fetchAuditLogs, exportAuditLogs } from '@/services/auditLogsService'
import { useDataGrid } from '@/composables/useDataGrid'
import { useDateFormat } from '@/composables/useDateFormat'
import type { AuditLogEntry } from '@/types/audit-logs'
import type { TableColumn } from '@/types/dashboard'

const { formatDateTime } = useDateFormat()

const logs = ref<AuditLogEntry[]>([])
const searchFilter = ref('')
const timeRangeFilter = ref('last30days')
const modalOpen = ref(false)
const selectedLog = ref<AuditLogEntry | null>(null)

const timeRangeOptions = [
  { value: 'yesterday', label: 'Yesterday' },
  { value: 'today', label: 'Today' },
  { value: 'last7days', label: 'Last 7 days' },
  { value: 'last30days', label: 'Last 30 days' },
  { value: 'last90days', label: 'Last 90 days' },
]

const columns: TableColumn[] = [
  { key: 'timestamp', label: 'Timestamp', sortable: true },
  { key: 'user', label: 'User', sortable: true },
  { key: 'object', label: 'Object', sortable: true },
  { key: 'action', label: 'Action', sortable: true },
  { key: 'note', label: 'Note', sortable: false },
  { key: 'actions', label: '', sortable: false, className: 'text-right w-16' },
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
  onDataLoad: async () => {
    loading.value = true
    try {
      const response = await fetchAuditLogs({
        page: currentPage.value,
        limit: itemsPerPage.value,
        sort: sortField.value,
        order: sortDirection.value,
        search: searchFilter.value || undefined,
        timeRange: timeRangeFilter.value || undefined,
      })
      logs.value = response.data
      totalPages.value = response.meta.totalPages
      totalItems.value = response.meta.total
    } finally {
      loading.value = false
    }
  },
  filters: [searchFilter, timeRangeFilter],
})

async function loadData() {
  loading.value = true
  try {
    const response = await fetchAuditLogs({
      page: currentPage.value,
      limit: itemsPerPage.value,
      sort: sortField.value,
      order: sortDirection.value,
      search: searchFilter.value || undefined,
      timeRange: timeRangeFilter.value || undefined,
    })
    logs.value = response.data
    totalPages.value = response.meta.totalPages
    totalItems.value = response.meta.total
  } finally {
    loading.value = false
  }
}

const handleOpenDetail = (log: AuditLogEntry) => {
  selectedLog.value = log
  modalOpen.value = true
}

const handleExport = async () => {
  try {
    const blob = await exportAuditLogs({
      search: searchFilter.value || undefined,
      timeRange: timeRangeFilter.value || undefined,
    })
    
    // Create download link
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `audit-logs-${new Date().toISOString().split('T')[0]}.csv`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to export audit logs:', error)
  }
}

const handleExportSingle = async () => {
  if (!selectedLog.value) return
  
  try {
    // In a real app, you might have a separate endpoint for single log export
    // For now, we'll just download the selected log as JSON
    const logJson = JSON.stringify(selectedLog.value, null, 2)
    const blob = new Blob([logJson], { type: 'application/json' })
    
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `audit-log-${selectedLog.value.id}.json`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to export audit log:', error)
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <main class="h-full overflow-y-auto"><div class="px-4 pb-4 pt-6">
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Audit Logs</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Track all system activities and user actions
      </p>
    </div>

    <Card>
      <div class="mb-3">
        <!-- Title and Export Button -->
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Audit Logs</h3>
          <Button variant="outline" @click="handleExport">
            <svg class="-ms-0.5 me-1.5 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
              <path fill-rule="evenodd" d="M9 7V2.2a2 2 0 0 0-.5.4l-4 3.9a2 2 0 0 0-.3.5H9Zm2 0V2h7a2 2 0 0 1 2 2v9.3l-2-2a1 1 0 0 0-1.4 1.4l.3.3h-6.6a1 1 0 1 0 0 2h6.6l-.3.3a1 1 0 0 0 1.4 1.4l2-2V20a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9h5a2 2 0 0 0 2-2Z" clip-rule="evenodd"></path>
            </svg>
            Export
          </Button>
        </div>
      </div>

      <DataGrid
        :columns="columns"
        :data="logs"
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
            placeholder="Search for user or object"
            width="w-80"
          />

          <TimeRangeFilterWithCustomRange
            v-model="timeRangeFilter"
            :options="timeRangeOptions"
          />
        </template>
        <!-- Custom cell templates -->
        <template #cell-timestamp="{ row }">
          <span class="font-medium text-gray-900 whitespace-nowrap dark:text-white">
            {{ formatDateTime((row as AuditLogEntry).timestamp) }}
          </span>
        </template>

        <template #cell-user="{ row }">
          <span class="whitespace-nowrap">{{ (row as AuditLogEntry).user }}</span>
        </template>

        <template #cell-object="{ row }">
          <span class="whitespace-nowrap">{{ (row as AuditLogEntry).object }}</span>
        </template>

        <template #cell-action="{ row }">
          <span class="whitespace-nowrap">{{ (row as AuditLogEntry).action }}</span>
        </template>

        <template #cell-note="{ row }">
          <span class="text-gray-500 dark:text-gray-400">{{ (row as AuditLogEntry).note }}</span>
        </template>

        <template #cell-actions="{ row }">
          <button
            @click="handleOpenDetail(row as AuditLogEntry)"
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

    <!-- Audit Log Detail Modal -->
    <AuditLogDetailModal
      v-model="modalOpen"
      :log="selectedLog"
      @export="handleExportSingle"
    />
  </div></main>
</template>

