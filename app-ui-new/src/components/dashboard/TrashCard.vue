<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
import { useApexChart, getChartColors, getBaseChartOptions } from '@/composables/useApexChart'
import { useDataGrid } from '@/composables/useDataGrid'
import { fetchTrashData } from '@/services/dashboardService'
import type { TrashData, TableColumn } from '@/types/dashboard'
import Card from '@/components/ui/Card.vue'
import DataGrid from '@/components/ui/DataGrid.vue'

const trashData = ref<TrashData | null>(null)
const chartEl = ref<HTMLElement | null>(null)
const chartMounted = ref(false)

const { initChart, setupResizeObserver, isDarkMode } = useApexChart({
  onDarkModeChange: () => {
    // Re-render chart like original Flowbite template
    // Only if chart was already mounted
    if (chartMounted.value && chartEl.value) {
      initChart(chartEl.value, getBarChartOptions())
      setupResizeObserver(chartEl.value)
    }
  },
})

const columns: TableColumn[] = [
  { key: 'topology', label: 'Topology', sortable: true, className: 'whitespace-nowrap' },
  { key: 'node', label: 'Node', sortable: true, className: 'whitespace-nowrap' },
  { key: 'message', label: 'Message', sortable: true, className: 'whitespace-nowrap truncate max-w-xs' },
  { key: 'count', label: 'Count', sortable: true, className: 'whitespace-nowrap' },
]

// Load data function
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
  } catch (error) {
    console.error('Error loading trash data:', error)
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
  defaultSort: { field: 'topology', direction: 'asc' },
  defaultPerPage: 5,
  onDataLoad: loadData,
})

// Initialize chart on mount
onMounted(async () => {
  try {
    await loadData()
    await nextTick()
    if (!chartEl.value || !trashData.value) {
      return
    }
    
    initChart(chartEl.value, getBarChartOptions())
    setupResizeObserver(chartEl.value)
    chartMounted.value = true
  } catch (error) {
    console.error('TrashCard mount error:', error)
  }
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
      height: 256,
      toolbar: {
        show: false,
      },
      background: colors.background,
    },
    plotOptions: {
      bar: {
        horizontal: true,
        borderRadius: 0,
        barHeight: '70%',
      },
    },
    colors: [colors.primary],
    dataLabels: {
      enabled: false,
    },
    stroke: {
      show: true,
      width: 0,
      colors: ['transparent'],
    },
    states: {
      hover: {
        filter: {
          type: 'darken',
          value: 1,
        },
      },
    },
    xaxis: {
      labels: {
        style: {
          colors: colors.text,
          fontFamily: 'Inter, sans-serif',
        },
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
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
    legend: {
      show: false,
    },
  }
}
</script>

<template>
  <Card>
    <div v-if="trashData">
      <!-- Header with metrics -->
      <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Trash</h3>
        <div class="flex items-center gap-6">
          <div class="flex flex-col items-center">
            <span class="text-xs text-gray-500 dark:text-gray-400">messages</span>
            <span class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ trashData.totalMessages }}
            </span>
          </div>
          <div class="flex flex-col items-center">
            <span class="text-xs text-gray-500 dark:text-gray-400">vs last day</span>
            <div class="flex items-center gap-1">
              <svg
                class="h-6 w-6 text-green-600 dark:text-green-400"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 19V5m0 14-4-4m4 4 4-4"
                />
              </svg>
              <span class="text-2xl font-bold text-green-600 dark:text-green-400">
                {{ trashData.vsLastDay }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Chart -->
      <div class="relative mb-4 h-64 overflow-visible">
        <div ref="chartEl" class="h-full"></div>
      </div>

      <!-- Table -->
      <DataGrid 
        :columns="columns" 
        :data="trashData.tableData"
        :current-page="currentPage"
        :total-pages="totalPages"
        :total-items="totalItems"
        :items-per-page="itemsPerPage"
        :sort-field="sortField"
        :sort-direction="sortDirection"
        :loading="loading"
        @page-change="handlePageChange"
        @per-page-change="handlePerPageChange"
        @sort="handleSort"
      >
        <template #cell-topology="{ value }">
          <span class="font-medium text-gray-900 dark:text-white">{{ value }}</span>
        </template>
        <template #cell-message="{ value }">
          <span class="max-w-xs truncate">{{ value }}</span>
        </template>
        <template #cell-count="{ value }">
          <span class="font-medium text-red-600 dark:text-red-400">{{ value }}</span>
        </template>
      </DataGrid>

      <div class="mt-4">
        <a
          href="#"
          class="text-sm font-medium text-primary-700 hover:underline dark:text-primary-500"
        >
          View all →
        </a>
      </div>
    </div>
    <div v-else class="flex items-center justify-center h-64">
      <div class="text-gray-500 dark:text-gray-400">Loading...</div>
    </div>
  </Card>
</template>

