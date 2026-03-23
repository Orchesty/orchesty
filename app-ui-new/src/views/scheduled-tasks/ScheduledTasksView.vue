<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Card from '@/components/ui/Card.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import CronSettingsModal from '@/components/scheduled-tasks/CronSettingsModal.vue'
import type { ScheduledTask } from '@/types/scheduled-tasks'
import type { TableColumn } from '@/types/dashboard'
import { fetchScheduledTasks, updateTaskStatus, updateTaskCrontab } from '@/services/scheduledTasksService'
import { getNextCronRun, formatNextRun } from '@/utils/cronParser'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import { useDataGrid } from '@/composables/useDataGrid'
import { useToast } from '@/composables/useToast'
import { useCronAlerts } from '@/composables/useCronAlerts'

const { showToast } = useToast()
const { refresh: refreshCronAlerts } = useCronAlerts()

const isMisconfigured = (row: ScheduledTask) =>
  row.nodeStatus && row.status === 'enabled' && !row.crontab

const tasks = ref<ScheduledTask[]>([])

// Modal state
const modalOpen = ref(false)
const selectedTask = ref<ScheduledTask | null>(null)

// Updating state for switches
const updatingTasks = ref<Set<string>>(new Set())

// Table columns
const columns: TableColumn[] = [
  { key: 'toggle', label: '', className: 'w-16' },
  { key: 'topology', label: 'Topology', sortable: false },
  { key: 'name', label: 'Name', sortable: false },
  { key: 'crontab', label: 'Crontab', sortable: false },
  { key: 'nextRun', label: 'Next Run', sortable: false },
  { key: 'status', label: 'Topology Status', sortable: false },
  { key: 'actions', label: '', className: 'text-right w-16' },
]

const loadData = async () => {
  loading.value = true
  try {
    const response = await fetchScheduledTasks({
      page: currentPage.value,
      limit: itemsPerPage.value,
      sort: sortField.value,
      order: sortDirection.value,
    })

    tasks.value = response.data
    totalPages.value = response.meta.totalPages
    totalItems.value = response.meta.totalItems
    refreshCronAlerts()
  } catch (error) {
    console.error('Failed to load scheduled tasks:', error)
  } finally {
    loading.value = false
  }
}

// Use DataGrid composable
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
  defaultSort: { field: 'name', direction: 'asc' },
  onDataLoad: loadData,
})

const handleToggleChange = async (task: ScheduledTask) => {
  const newEnabled = !task.nodeStatus

  updatingTasks.value.add(task.id)

  try {
    await updateTaskStatus(task.nodeId, newEnabled)
    await loadData()
    showToast(`Task ${newEnabled ? 'enabled' : 'disabled'} successfully`, 'success')
  } catch (error) {
    console.error('Failed to update task status:', error)
    showToast('Failed to update task status', 'error')
    await loadData()
  } finally {
    updatingTasks.value.delete(task.id)
  }
}

const handleSettingsClick = (task: ScheduledTask) => {
  selectedTask.value = task
  modalOpen.value = true
}

const handleCronSave = async (taskId: string, crontab: string, params: string) => {
  try {
    await updateTaskCrontab(taskId, crontab, params)
    // Reload data to get updated crontab
    await loadData()
    showToast('Crontab updated successfully', 'success')
  } catch (error) {
    console.error('Failed to update crontab:', error)
    showToast('Failed to update crontab', 'error')
  }
}

// Recalculate nextRun for tasks whose scheduled time has passed
const refreshNextRuns = () => {
  const now = new Date()
  for (const task of tasks.value) {
    if (!task.nodeStatus || !task.crontab || task.status === 'disabled') {
      task.nextRun = null
      continue
    }
    if (task.nextRun && task.nextRun <= now) {
      task.nextRun = getNextCronRun(task.crontab)
    }
  }
}

let nextRunTimer: ReturnType<typeof setInterval> | null = null

onMounted(() => {
  loadData()
  nextRunTimer = setInterval(refreshNextRuns, 60_000)
})

onBeforeUnmount(() => {
  if (nextRunTimer) {
    clearInterval(nextRunTimer)
  }
})
</script>

<template>
  <DashboardLayout>
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Scheduled Tasks</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Manage scheduled tasks and cron events
      </p>
    </div>

    <!-- Scheduled Tasks Table Card -->
    <Card>
      <div class="mb-3">
        <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">Scheduled Tasks</h3>
      </div>

      <DataGrid
        :columns="columns"
        :data="tasks"
        :loading="loading"
        :current-page="currentPage"
        :total-pages="totalPages"
        :total-items="totalItems"
        :items-per-page="itemsPerPage"
        :sort-field="sortField"
        :sort-direction="sortDirection"
        @page-change="handlePageChange"
        @per-page-change="handlePerPageChange"
        @sort="handleSort"
      >
        <!-- Toggle Switch Cell -->
        <template #cell-toggle="{ row }">
          <label class="relative inline-flex cursor-pointer items-center">
            <input
              type="checkbox"
              :checked="row.nodeStatus"
              :disabled="updatingTasks.has(row.id)"
              class="peer sr-only"
              @change="handleToggleChange(row as ScheduledTask)"
            />
            <div
              :class="[
                'relative h-5 w-9 rounded-full bg-gray-200 after:absolute after:start-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[\'\'] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-checked:bg-primary-600 dark:peer-focus:ring-primary-800 rtl:peer-checked:after:-translate-x-full',
                {
                  'cursor-not-allowed opacity-50': updatingTasks.has(row.id),
                },
              ]"
            ></div>
          </label>
        </template>

        <!-- Name Cell -->
        <template #cell-name="{ row }">
          <span class="font-medium text-gray-900 dark:text-white">
            {{ row.name }}
          </span>
        </template>

        <!-- Topology Cell -->
        <template #cell-topology="{ row }">
          <RouterLink
            :to="`/topologies/${row.topologyId}`"
            class="font-medium text-gray-900 hover:underline dark:text-white"
          >
            {{ row.topology }}
          </RouterLink>
        </template>

        <!-- Crontab Cell -->
        <template #cell-crontab="{ row }">
          <span v-if="row.crontab" class="font-mono text-xs">{{ row.crontab }}</span>
          <span v-else-if="isMisconfigured(row)" class="inline-flex items-center gap-1 text-xs font-medium text-red-600 dark:text-red-400">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
            Cron is not set
          </span>
          <span v-else class="font-mono text-xs">-</span>
        </template>

        <!-- Next Run Cell -->
        <template #cell-nextRun="{ row }">
          <span v-if="row.nextRun" class="text-sm text-gray-700 dark:text-gray-300">
            {{ formatNextRun(row.nextRun) }}
          </span>
          <span v-else class="text-sm text-gray-400 dark:text-gray-500">-</span>
        </template>

        <!-- Status Cell -->
        <template #cell-status="{ row }">
          <StatusBadge :variant="row.status === 'enabled' ? 'green' : 'gray'">
            {{ row.status === 'enabled' ? 'Enabled' : row.status === 'disabled' ? 'Disabled' : row.status }}
          </StatusBadge>
        </template>

        <!-- Actions Cell -->
        <template #cell-actions="{ row }">
          <div class="flex items-center justify-end gap-2">
            <button
              type="button"
              title="Settings"
              class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              @click="handleSettingsClick(row as ScheduledTask)"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                height="24px"
                viewBox="0 -960 960 960"
                width="24px"
                fill="currentColor"
              >
                <path
                  d="m387.69-100-15.23-121.85q-16.07-5.38-32.96-15.07-16.88-9.7-30.19-20.77L196.46-210l-92.3-160 97.61-73.77q-1.38-8.92-1.96-17.92-.58-9-.58-17.93 0-8.53.58-17.34t1.96-19.27L104.16-590l92.3-159.23 112.46 47.31q14.47-11.46 30.89-20.96t32.27-15.27L387.69-860h184.62l15.23 122.23q18 6.54 32.57 15.27 14.58 8.73 29.43 20.58l114-47.31L855.84-590l-99.15 74.92q2.15 9.69 2.35 18.12.19 8.42.19 16.96 0 8.15-.39 16.58-.38 8.42-2.76 19.27L854.46-370l-92.31 160-112.61-48.08q-14.85 11.85-30.31 20.96-15.46 9.12-31.69 14.89L572.31-100H387.69Zm92.77-260q49.92 0 84.96-35.04 35.04-35.04 35.04-84.96 0-49.92-35.04-84.96Q530.38-600 480.46-600q-50.54 0-85.27 35.04T360.46-480q0 49.92 34.73 84.96Q429.92-360 480.46-360Z"
                />
              </svg>
              <span class="sr-only">Settings</span>
            </button>
          </div>
        </template>
      </DataGrid>
    </Card>

    <!-- Cron Settings Modal -->
    <CronSettingsModal
      v-model="modalOpen"
      :task="selectedTask"
      @save="handleCronSave"
    />
  </DashboardLayout>
</template>

