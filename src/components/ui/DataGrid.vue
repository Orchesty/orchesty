<script setup lang="ts">
import { computed, ref, watch, onBeforeUnmount, nextTick } from 'vue'
import type { TableColumn } from '@/types/dashboard'
import type { ActionConfig, BulkAction } from '@/types/datagrid'
import DataGridActions from './datagrid/DataGridActions.vue'
import LoadingSpinner from './LoadingSpinner.vue'

interface SortConfig {
  field: string
  direction: 'asc' | 'desc'
}

interface Props {
  columns: TableColumn[]
  data: Record<string, any>[]
  loading?: boolean
  // Pagination
  currentPage?: number
  totalPages?: number
  totalItems?: number
  itemsPerPage?: number
  perPageOptions?: number[]
  // Sorting
  sortField?: string
  sortDirection?: 'asc' | 'desc'
  // Actions
  actions?: ActionConfig[]
  // Pagination visibility
  hidePagination?: boolean
  // Table layout
  tableFixed?: boolean
  // Bulk Actions
  bulkActions?: BulkAction[]
  selectedRows?: Set<string>
  rowIdKey?: string
  // Refresh button
  showRefresh?: boolean
  // Row class function
  rowClass?: (row: Record<string, any>) => string
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  currentPage: 1,
  totalPages: 1,
  totalItems: 0,
  itemsPerPage: 25,
  perPageOptions: () => [5, 10, 25, 50, 100],
  hidePagination: false,
  sortField: '',
  sortDirection: 'asc',
  actions: undefined,
  bulkActions: undefined,
  selectedRows: () => new Set<string>(),
  rowIdKey: 'id',
  showRefresh: false,
})

const emit = defineEmits<{
  'page-change': [page: number]
  'per-page-change': [perPage: number]
  'sort': [config: SortConfig]
  'update:selectedRows': [value: Set<string>]
  'refresh': []
}>()

// Track if we have data loaded (for overlay spinner logic)
const hasData = ref(false)
const showLoadingOverlay = ref(false)
let loadingTimeout: ReturnType<typeof setTimeout> | null = null

// Watch loading state to show overlay spinner only after 500ms delay
watch(
  () => props.loading,
  (isLoading) => {
    if (isLoading && hasData.value) {
      // If we already have data and loading starts, show spinner after 500ms
      loadingTimeout = setTimeout(() => {
        showLoadingOverlay.value = true
      }, 500)
    } else {
      // Clear timeout and hide overlay immediately when loading stops
      if (loadingTimeout) {
        clearTimeout(loadingTimeout)
        loadingTimeout = null
      }
      showLoadingOverlay.value = false
    }
  },
  { immediate: true }
)

// Track when data is available
watch(
  () => props.data,
  (newData) => {
    if (newData && newData.length > 0) {
      hasData.value = true
    }
  },
  { immediate: true }
)

// Cleanup timeout on unmount
onBeforeUnmount(() => {
  if (loadingTimeout) {
    clearTimeout(loadingTimeout)
  }
})

// Bulk selection logic
const internalSelectedRows = ref<Set<string>>(new Set())
const selectAllCheckbox = ref<HTMLInputElement | null>(null)

// Sync internal state with prop
watch(
  () => props.selectedRows,
  (newValue) => {
    if (newValue) {
      internalSelectedRows.value = new Set(newValue)
    }
  },
  { immediate: true }
)

const allRowsSelected = computed(() => {
  if (!props.data || props.data.length === 0) return false
  return props.data.every((row) => internalSelectedRows.value.has(row[props.rowIdKey]))
})

const someRowsSelected = computed(() => {
  if (!props.data || props.data.length === 0) return false
  return props.data.some((row) => internalSelectedRows.value.has(row[props.rowIdKey]))
})

// Update indeterminate state of select-all checkbox
watch(
  [someRowsSelected, allRowsSelected],
  async () => {
    await nextTick()
    if (selectAllCheckbox.value) {
      selectAllCheckbox.value.indeterminate = someRowsSelected.value && !allRowsSelected.value
    }
  }
)

const toggleSelectAll = () => {
  if (!props.data) return
  
  const newSelection = new Set(internalSelectedRows.value)
  
  if (allRowsSelected.value) {
    // Deselect all rows on current page
    props.data.forEach((row) => {
      newSelection.delete(row[props.rowIdKey])
    })
  } else {
    // Select all rows on current page
    props.data.forEach((row) => {
      newSelection.add(row[props.rowIdKey])
    })
  }
  
  internalSelectedRows.value = newSelection
  emit('update:selectedRows', newSelection)
}

const toggleRowSelection = (rowId: string) => {
  const newSelection = new Set(internalSelectedRows.value)
  
  if (newSelection.has(rowId)) {
    newSelection.delete(rowId)
  } else {
    newSelection.add(rowId)
  }
  
  internalSelectedRows.value = newSelection
  emit('update:selectedRows', newSelection)
}

const isRowSelected = (rowId: string) => {
  return internalSelectedRows.value.has(rowId)
}

const hasSelectedRows = computed(() => {
  return internalSelectedRows.value.size > 0
})

// Automatically add columns for bulk actions and actions
const columnsWithActions = computed(() => {
  let columns = [...props.columns]
  
  // Add checkbox column at the beginning if bulk actions are enabled
  if (props.bulkActions && props.bulkActions.length > 0) {
    const hasCheckboxColumn = columns.some(col => col.key === '__checkbox')
    if (!hasCheckboxColumn) {
      columns = [
        { key: '__checkbox', label: '', className: 'w-4' },
        ...columns
      ]
    }
  }
  
  // Add actions column at the end if actions prop is provided
  if (props.actions && props.actions.length > 0) {
    const hasActionsColumn = columns.some(col => col.key === 'actions')
    if (!hasActionsColumn) {
      columns = [
        ...columns,
        { key: 'actions', label: '', className: 'text-right' }
      ]
    }
  }
  
  return columns
})

const getCellValue = (row: Record<string, any>, key: string) => {
  return row[key]
}

const handleSort = (column: TableColumn) => {
  if (!column.sortable) return

  let direction: 'asc' | 'desc' = 'asc'
  
  // If clicking the same column, toggle direction
  if (props.sortField === column.key) {
    direction = props.sortDirection === 'asc' ? 'desc' : 'asc'
  }

  emit('sort', { field: column.key, direction })
}

const handlePageChange = (page: number) => {
  emit('page-change', page)
}

const handlePerPageChange = (event: Event) => {
  const target = event.target as HTMLSelectElement
  emit('per-page-change', parseInt(target.value))
}

// Calculate display range
const displayStart = computed(() => {
  if (props.totalItems === 0) return 0
  return (props.currentPage - 1) * props.itemsPerPage + 1
})

const displayEnd = computed(() => {
  if (props.totalItems === 0) return 0
  return Math.min(props.currentPage * props.itemsPerPage, props.totalItems)
})

// Generate page numbers for pagination
const pageNumbers = () => {
  const pages: (number | string)[] = []
  const maxVisible = 5

  if (props.totalPages <= maxVisible) {
    for (let i = 1; i <= props.totalPages; i++) {
      pages.push(i)
    }
  } else {
    pages.push(1)

    if (props.currentPage > 3) {
      pages.push('...')
    }

    const start = Math.max(2, props.currentPage - 1)
    const end = Math.min(props.totalPages - 1, props.currentPage + 1)

    for (let i = start; i <= end; i++) {
      pages.push(i)
    }

    if (props.currentPage < props.totalPages - 2) {
      pages.push('...')
    }

    pages.push(props.totalPages)
  }

  return pages
}
</script>

<template>
  <div>
    <!-- Filters area -->
    <div class="mb-3 flex flex-col gap-3">
      <!-- Search row (separate, right-aligned) -->
      <div v-if="$slots.search" class="flex justify-end">
        <slot name="search"></slot>
      </div>

      <!-- Main row: quick-filters + bulk actions (left) | filters + refresh (right) -->
      <div class="flex flex-col gap-3 md:flex-row md:items-center" :class="($slots['quick-filters'] || (bulkActions && bulkActions.length > 0)) ? 'md:justify-between' : 'md:justify-end'">
        <!-- Left side: Quick Filters and/or Bulk Actions -->
        <div v-if="$slots['quick-filters'] || (bulkActions && bulkActions.length > 0)" class="flex flex-col gap-3">
          <div v-if="$slots['quick-filters']">
            <slot name="quick-filters"></slot>
          </div>
          
          <!-- Bulk Actions -->
          <div
            v-if="bulkActions && bulkActions.length > 0"
            class="flex items-center gap-4 pl-6"
          >
            <div class="flex items-center gap-2">
              <input
                ref="selectAllCheckbox"
                type="checkbox"
                :checked="allRowsSelected"
                class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                @change="toggleSelectAll"
              />
              <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Select All
              </span>
            </div>
            <button
              v-for="action in bulkActions"
              :key="action.label"
              type="button"
              :disabled="!hasSelectedRows"
              class="text-sm font-medium disabled:cursor-not-allowed disabled:opacity-50"
              :class="{
                'text-primary-600 hover:text-primary-700 hover:underline dark:text-primary-500': action.variant === 'primary' && hasSelectedRows,
                'text-red-600 hover:text-red-700 hover:underline dark:text-red-500': action.variant === 'danger' && hasSelectedRows,
                'text-gray-600 hover:text-gray-700 hover:underline dark:text-gray-400': action.variant === 'secondary' && hasSelectedRows,
                'text-gray-400 dark:text-gray-600': !hasSelectedRows
              }"
              @click="action.onClick(internalSelectedRows)"
            >
              {{ action.label }}
            </button>
          </div>
        </div>

        <!-- Right side: Regular Filters + Refresh -->
        <div v-if="$slots.filters || showRefresh" class="flex flex-wrap items-center justify-end gap-2">
          <slot name="filters"></slot>
          <button
            v-if="showRefresh"
            type="button"
            title="Refresh"
            :disabled="loading"
            class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-400 transition-colors hover:text-gray-900 focus:outline-hidden dark:text-gray-500 dark:hover:text-white"
            @click="emit('refresh')"
          >
            <svg
              class="h-5 w-5 transition-transform duration-500"
              :class="{ 'animate-spin': loading }"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M1 4v6h6M23 20v-6h-6" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4-4.64 4.36A9 9 0 0 1 3.51 15" />
            </svg>
            <span class="sr-only">Refresh</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div class="relative overflow-hidden">
      <!-- Loading Overlay (shown after 500ms if data exists) -->
      <div
        v-if="showLoadingOverlay"
        class="absolute inset-0 z-10 flex items-center justify-center bg-white/80 dark:bg-gray-800/80"
      >
        <LoadingSpinner size="sm" />
      </div>

      <table :class="['w-full max-w-full text-left text-sm text-gray-500 dark:text-gray-400', tableFixed ? 'table-fixed' : 'table-auto']">
        <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
          <tr>
            <th 
              v-for="column in columnsWithActions" 
              :key="column.key" 
              scope="col" 
              class="whitespace-nowrap px-6 py-3 font-semibold"
              :class="[
                column.className,
                { 'cursor-pointer hover:text-gray-900 dark:hover:text-white': column.sortable && column.key !== '__checkbox' }
              ]"
              @click="column.key !== '__checkbox' ? handleSort(column) : undefined"
            >
              <!-- Checkbox column: Empty header -->
              <template v-if="column.key === '__checkbox'">
                <!-- Empty - Select All is now in bulk actions bar -->
              </template>
              <!-- Regular column -->
              <template v-else>
                {{ column.label }}
                <svg
                  v-if="column.sortable"
                  class="ml-1 inline-block h-4 w-4"
                  :class="{
                    'text-gray-900 dark:text-white': sortField === column.key,
                    'text-gray-400': sortField !== column.key
                  }"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                  xmlns="http://www.w3.org/2000/svg"
                  aria-hidden="true"
                >
                  <path
                    clip-rule="evenodd"
                    fill-rule="evenodd"
                    d="M10 3a.75.75 0 01.55.24l3.25 3.5a.75.75 0 11-1.1 1.02L10 4.852 7.3 7.76a.75.75 0 01-1.1-1.02l3.25-3.5A.75.75 0 0110 3zm-3.76 9.2a.75.75 0 011.06.04l2.7 2.908 2.7-2.908a.75.75 0 111.1 1.02l-3.25 3.5a.75.75 0 01-1.1 0l-3.25-3.5a.75.75 0 01.04-1.06z"
                  />
                </svg>
              </template>
            </th>
          </tr>
        </thead>
        <!-- Data rows - show even during loading if we have data -->
        <tbody v-if="data && data.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
          <tr
            v-for="(row, index) in data"
            :key="index"
            :class="rowClass ? rowClass(row) : 'bg-white dark:bg-gray-800'"
          >
            <td
              v-for="column in columnsWithActions"
              :key="column.key"
              class="px-6 py-4 min-w-0 break-words"
              :class="column.className"
            >
              <!-- Checkbox column -->
              <template v-if="column.key === '__checkbox'">
                <input
                  type="checkbox"
                  :checked="isRowSelected(row[rowIdKey])"
                  class="h-4 w-4 rounded-sm border-gray-300 bg-gray-100 text-primary-600 focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:ring-offset-gray-800 dark:focus:ring-primary-600"
                  @change="toggleRowSelection(row[rowIdKey])"
                />
              </template>
              <!-- Actions column with DataGridActions or slot -->
              <template v-else-if="column.key === 'actions'">
                <DataGridActions v-if="actions && actions.length > 0" :actions="actions" :row="row" />
                <slot v-else name="cell-actions" :row="row" :value="getCellValue(row, column.key)"></slot>
              </template>
              <!-- Regular columns -->
              <slot v-else :name="`cell-${column.key}`" :row="row" :value="getCellValue(row, column.key)">
                {{ getCellValue(row, column.key) }}
              </slot>
            </td>
          </tr>
        </tbody>
        <!-- Initial loading state (no data yet) -->
        <tbody v-else-if="loading && !hasData">
          <tr>
            <td :colspan="columnsWithActions.length" class="px-4 py-8 text-center">
              <LoadingSpinner size="sm" />
            </td>
          </tr>
        </tbody>
        <!-- Empty state -->
        <tbody v-else>
          <tr>
            <td :colspan="columnsWithActions.length" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
              No data available
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="!hidePagination && data && data.length > 0" class="flex flex-col items-start justify-between space-y-3 pt-4 md:flex-row md:items-center md:space-y-0">
      <div class="flex items-center space-x-3">
        <label for="rows-per-page" class="text-sm font-normal text-gray-500 dark:text-gray-400">Rows per page</label>
        <select
          id="rows-per-page"
          :value="itemsPerPage"
          @change="handlePerPageChange"
          class="block rounded-lg border border-gray-300 bg-gray-50 py-1.5 pl-3.5 pr-6 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500"
        >
          <option v-for="option in perPageOptions" :key="option" :value="option">{{ option }}</option>
        </select>
        <div class="text-xs font-normal text-gray-500 dark:text-gray-400">
          <span class="font-semibold text-gray-900 dark:text-white">{{ displayStart }}-{{ displayEnd }}</span>
          of
          <span class="font-semibold text-gray-900 dark:text-white">{{ totalItems }}</span>
        </div>
      </div>
      <ul class="inline-flex -space-x-px items-stretch">
        <li>
          <button
            @click="handlePageChange(currentPage - 1)"
            :disabled="currentPage === 1"
            class="ml-0 flex h-full items-center justify-center rounded-l-lg border border-gray-300 bg-white px-3 py-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
          >
            <span class="sr-only">Previous</span>
            <svg class="h-5 w-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"></path>
            </svg>
          </button>
        </li>
        <li v-for="(page, idx) in pageNumbers()" :key="idx">
          <button
            v-if="page === '...'"
            disabled
            class="flex items-center justify-center border border-gray-300 bg-white px-3 py-2 text-sm leading-tight text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400"
          >
            {{ page }}
          </button>
          <button
            v-else
            @click="handlePageChange(page as number)"
            :class="[
              'flex items-center justify-center border px-3 py-2 text-sm leading-tight',
              currentPage === page
                ? 'z-10 border-primary-300 bg-primary-50 text-primary-600 dark:border-gray-700 dark:bg-gray-700 dark:text-white'
                : 'border-gray-300 bg-white text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white',
            ]"
          >
            {{ page }}
          </button>
        </li>
        <li>
          <button
            @click="handlePageChange(currentPage + 1)"
            :disabled="currentPage === totalPages"
            class="flex h-full items-center justify-center rounded-r-lg border border-gray-300 bg-white px-3 py-1.5 leading-tight text-gray-500 hover:bg-gray-100 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
          >
            <span class="sr-only">Next</span>
            <svg class="h-5 w-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
            </svg>
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>
