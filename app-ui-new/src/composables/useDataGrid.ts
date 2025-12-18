import { ref, watch, type Ref } from 'vue'

export interface UseDataGridOptions {
  defaultSort?: {
    field: string
    direction: 'asc' | 'desc'
  }
  defaultPerPage?: number
  onDataLoad: () => Promise<void> | void
  filters?: Ref<any>[]
}

export function useDataGrid(options: UseDataGridOptions) {
  // Pagination state
  const currentPage = ref(1)
  const itemsPerPage = ref(options.defaultPerPage ?? 10)
  const totalPages = ref(1)
  const totalItems = ref(0)

  // Sorting state
  const sortField = ref(options.defaultSort?.field ?? 'id')
  const sortDirection = ref<'asc' | 'desc'>(options.defaultSort?.direction ?? 'asc')

  // Loading state
  const loading = ref(true)

  // Pagination handlers
  const handlePageChange = (page: number) => {
    currentPage.value = page
    options.onDataLoad()
  }

  const handlePerPageChange = (perPage: number) => {
    itemsPerPage.value = perPage
    currentPage.value = 1
    options.onDataLoad()
  }

  // Sorting handler
  const handleSort = (config: { field: string; direction: 'asc' | 'desc' }) => {
    sortField.value = config.field
    sortDirection.value = config.direction
    options.onDataLoad()
  }

  // Watch filters if provided
  if (options.filters && options.filters.length > 0) {
    watch(
      options.filters,
      () => {
        currentPage.value = 1
        options.onDataLoad()
      },
      { deep: true }
    )
  }

  return {
    // State
    currentPage,
    itemsPerPage,
    totalPages,
    totalItems,
    sortField,
    sortDirection,
    loading,
    // Methods
    handlePageChange,
    handlePerPageChange,
    handleSort,
  }
}
