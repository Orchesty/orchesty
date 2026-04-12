<script setup lang="ts">
import { ref, onMounted, onActivated, nextTick } from 'vue'
import { useApexChart, getChartColors, getBaseChartOptions } from '@/composables/useApexChart'
import { useDataGrid } from '@/composables/useDataGrid'
import { fetchTrashData } from '@/services/dashboardService'
import { approveByFilter, rejectByFilter } from '@/services/trashService'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'
import { useToast } from '@/composables/useToast'
import type { TrashData, TrashTableRow, TableColumn } from '@/types/dashboard'
import Card from '@/components/ui/Card.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import GridLink from '@/components/ui/datagrid/GridLink.vue'
import MoreActions from '@/components/ui/MoreActions.vue'
import type { MoreActionsSection } from '@/components/ui/MoreActions.vue'
import Confirm from '@/components/ui/Confirm.vue'

const emit = defineEmits<{
  'view-messages': [row: TrashTableRow]
}>()

const { getTopologyNameWithVersion, getNodeName } = useTopologyNodeMappings()
const { showToast } = useToast()

const trashData = ref<TrashData | null>(null)
const chartEl = ref<HTMLElement | null>(null)
const chartMounted = ref(false)

const { initChart, setupResizeObserver, isDarkMode } = useApexChart({
  onDarkModeChange: () => {
    if (chartMounted.value && chartEl.value) {
      initChart(chartEl.value, getBarChartOptions())
      setupResizeObserver(chartEl.value)
    }
  },
})

const columns: TableColumn[] = [
  { key: 'topology', label: 'Topology', sortable: false },
  { key: 'node', label: 'Node', sortable: false },
  { key: 'message', label: 'Message', sortable: false, className: 'truncate max-w-xs' },
  { key: 'count', label: 'Count', sortable: true },
  { key: 'actions', label: '', className: 'text-right w-16' },
]

// Confirm modal state
const confirmOpen = ref(false)
const confirmAction = ref<'approve' | 'reject'>('approve')
const confirmRow = ref<TrashTableRow | null>(null)
const confirmProcessing = ref(false)

function getRowActions(row: TrashTableRow): MoreActionsSection[] {
  return [
    {
      items: [
        {
          type: 'button',
          label: 'View messages',
          onClick: () => emit('view-messages', row),
        },
      ],
    },
    {
      items: [
        {
          type: 'button',
          label: 'Approve all',
          onClick: () => openConfirm('approve', row),
        },
        {
          type: 'button',
          label: 'Reject all',
          onClick: () => openConfirm('reject', row),
        },
      ],
    },
  ]
}

function openConfirm(action: 'approve' | 'reject', row: TrashTableRow) {
  confirmAction.value = action
  confirmRow.value = row
  confirmOpen.value = true
}

async function handleConfirm() {
  const row = confirmRow.value
  if (!row) return

  const filter = {
    topologyId: row.topologyId,
    nodeId: row.nodeId,
    resultMessage: row.message || '',
  }

  confirmProcessing.value = true
  try {
    if (confirmAction.value === 'approve') {
      await approveByFilter(filter)
      showToast(`Messages approved successfully`, 'success')
    } else {
      await rejectByFilter(filter)
      showToast(`Messages rejected successfully`, 'success')
    }
    confirmOpen.value = false
    loadData()
  } catch (error) {
    console.error(`Failed to ${confirmAction.value} messages:`, error)
    showToast(`Failed to ${confirmAction.value} messages`, 'error')
    confirmOpen.value = false
  } finally {
    confirmProcessing.value = false
  }
}

const loadData = async () => {
  loading.value = true

  try {
    const response = await fetchTrashData({
      page: currentPage.value,
      limit: itemsPerPage.value,
      sortBy: sortField.value,
      sortOrder: sortDirection.value,
    })

    trashData.value = response
    totalPages.value = response.meta.totalPages
    totalItems.value = response.meta.totalItems

    await nextTick()
    if (chartMounted.value && chartEl.value) {
      initChart(chartEl.value, getBarChartOptions())
      setupResizeObserver(chartEl.value)
    }
  } catch (error) {
    console.error('Error loading trash data:', error)
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
  defaultSort: { field: 'count', direction: 'desc' },
  defaultPerPage: 25,
  onDataLoad: loadData,
})

const mounted = ref(false)

onMounted(async () => {
  try {
    await loadData()
    await nextTick()
    if (!chartEl.value || !trashData.value) return

    initChart(chartEl.value, getBarChartOptions())
    setupResizeObserver(chartEl.value)
    chartMounted.value = true
    mounted.value = true
  } catch (error) {
    console.error('FailedMessagesByTopologyTab mount error:', error)
  }
})

onActivated(() => {
  if (mounted.value) loadData()
})

const getBarChartOptions = () => {
  const colors = getChartColors(isDarkMode.value)

  return {
    ...getBaseChartOptions(isDarkMode.value),
    series: [
      {
        name: 'Messages',
        data: trashData.value?.chartData || [],
      },
    ],
    chart: {
      type: 'bar',
      height: 320,
      toolbar: { show: false },
      background: 'transparent',
    },
    plotOptions: {
      bar: {
        horizontal: true,
        borderRadius: 0,
        barHeight: '70%',
      },
    },
    colors: [colors.primary],
    dataLabels: { enabled: false },
    stroke: {
      show: true,
      width: 0,
      colors: ['transparent'],
    },
    states: {
      hover: {
        filter: { type: 'darken', value: 1 },
      },
    },
    xaxis: {
      labels: {
        style: {
          colors: colors.text,
          fontFamily: 'Inter, sans-serif',
        },
      },
      axisBorder: { show: false },
      axisTicks: { show: false },
    },
    yaxis: {
      labels: {
        show: true,
        style: {
          colors: colors.text,
          fontFamily: 'Inter, sans-serif',
        },
      },
    },
    legend: { show: false },
  }
}
</script>

<template>
  <div class="space-y-6">
    <Card>
      <div v-if="trashData">
        <div class="mb-4 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Failed Messages by Topology</h3>
          <div class="flex flex-col items-center">
            <span class="text-xs text-gray-500 dark:text-gray-400">total messages</span>
            <span class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ trashData.totalMessages }}
            </span>
          </div>
        </div>

        <div class="relative h-80 overflow-visible">
          <div ref="chartEl" class="h-full"></div>
        </div>
      </div>
      <div v-else class="flex h-80 items-center justify-center">
        <div class="text-gray-500 dark:text-gray-400">Loading...</div>
      </div>
    </Card>

    <Card>
      <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Details</h3>

      <DataGrid
        :columns="columns"
        :data="trashData?.tableData || []"
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
        <template #cell-topology="{ row }">
          <GridLink v-if="row.topologyId" :to="{ name: 'topology-detail', params: { id: row.topologyId } }">
            {{ getTopologyNameWithVersion(row.topologyId) }}
          </GridLink>
          <span v-else class="text-gray-400">—</span>
        </template>
        <template #cell-node="{ row }">
          <span class="text-gray-900 dark:text-white">{{ getNodeName(row.nodeId) }}</span>
        </template>
        <template #cell-count="{ value }">
          <span class="font-medium text-red-600 dark:text-red-400">{{ value }}</span>
        </template>
        <template #cell-actions="{ row }">
          <div class="flex items-center justify-end">
            <MoreActions
              :id="`trash-row-${row.topologyId}-${row.nodeId}`"
              :sections="getRowActions(row as TrashTableRow)"
            />
          </div>
        </template>
      </DataGrid>
    </Card>

    <Confirm
      v-model="confirmOpen"
      :id="`trash-${confirmAction}-confirm`"
      :confirm-text="confirmAction === 'approve' ? 'Yes, approve all' : 'Yes, reject all'"
      cancel-text="Cancel"
      :confirm-variant="confirmAction === 'approve' ? 'primary' : 'danger'"
      :loading="confirmProcessing"
      @confirm="handleConfirm"
    >
      <svg
        class="mx-auto mb-4 h-12 w-12 text-gray-400 dark:text-gray-200"
        aria-hidden="true"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 20 20"
      >
        <path
          stroke="currentColor"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
        />
      </svg>
      <h3 class="mb-2 text-lg font-normal text-gray-500 dark:text-gray-400">
        Are you sure you want to {{ confirmAction }} all
        <span class="font-semibold text-gray-900 dark:text-white">{{ confirmRow?.count }}</span>
        {{ (confirmRow?.count ?? 0) === 1 ? 'message' : 'messages' }}?
      </h3>
      <p v-if="confirmRow" class="text-sm text-gray-400 dark:text-gray-500">
        {{ getTopologyNameWithVersion(confirmRow.topologyId) }} / {{ getNodeName(confirmRow.nodeId) }}
        <template v-if="confirmRow.message">
          — {{ confirmRow.message }}
        </template>
      </p>
    </Confirm>
  </div>
</template>
