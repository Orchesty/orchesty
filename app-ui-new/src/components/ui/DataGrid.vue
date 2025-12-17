<script setup lang="ts">
import { computed } from 'vue'
import type { TableColumn } from '@/types/dashboard'
import type { ActionConfig } from '@/types/datagrid'
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
}

const props = withDefaults(defineProps<Props>(), {
  loading: false,
  currentPage: 1,
  totalPages: 1,
  totalItems: 0,
  itemsPerPage: 10,
  perPageOptions: () => [10, 25, 50, 100],
  sortField: '',
  sortDirection: 'asc',
  actions: undefined,
})

const emit = defineEmits<{
  'page-change': [page: number]
  'per-page-change': [perPage: number]
  'sort': [config: SortConfig]
}>()

// Automatically add actions column if actions prop is provided
const columnsWithActions = computed(() => {
  if (!props.actions || props.actions.length === 0) {
    return props.columns
  }
  
  // Check if actions column already exists
  const hasActionsColumn = props.columns.some(col => col.key === 'actions')
  if (hasActionsColumn) {
    return props.columns
  }
  
  // Add actions column
  return [
    ...props.columns,
    { key: 'actions', label: '', className: 'text-right' }
  ]
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
    <!-- Filters slot -->
    <div v-if="$slots.filters" class="mb-3">
      <slot name="filters"></slot>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
        <thead class="bg-gray-50 text-xs uppercase text-gray-700 dark:bg-gray-700 dark:text-gray-400">
          <tr>
            <th 
              v-for="column in columnsWithActions" 
              :key="column.key" 
              scope="col" 
              class="whitespace-nowrap px-6 py-3 font-semibold"
              :class="[
                column.className,
                { 'cursor-pointer hover:text-gray-900 dark:hover:text-white': column.sortable }
              ]"
              @click="handleSort(column)"
            >
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
            </th>
          </tr>
        </thead>
        <tbody v-if="!loading && data.length > 0" class="divide-y divide-gray-100 dark:divide-gray-700">
          <tr
            v-for="(row, index) in data"
            :key="index"
            class="bg-white dark:bg-gray-800"
          >
            <td
              v-for="column in columnsWithActions"
              :key="column.key"
              class="px-6 py-4"
              :class="column.className"
            >
              <!-- Actions column with DataGridActions or slot -->
              <template v-if="column.key === 'actions'">
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
        <tbody v-else-if="loading">
          <tr>
            <td :colspan="columnsWithActions.length" class="px-4 py-8 text-center">
              <LoadingSpinner size="sm" />
            </td>
          </tr>
        </tbody>
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
    <div v-if="!loading && data.length > 0" class="flex flex-col items-start justify-between space-y-3 pt-4 md:flex-row md:items-center md:space-y-0">
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
