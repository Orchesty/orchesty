<script setup lang="ts">
import { ref, onMounted } from 'vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import Confirm from '@/components/ui/Confirm.vue'
import WorkerModal from '@/components/settings/WorkerModal.vue'
import { useDataGrid } from '@/composables/useDataGrid'
import { fetchWorkers, createWorker, updateWorker, deleteWorker } from '@/services/workersService'
import type { Worker } from '@/types/settings'

const workers = ref<Worker[]>([])

// Worker modal state
const workerModalOpen = ref(false)
const selectedWorker = ref<Worker | null>(null)
const workerModalMode = ref<'create' | 'edit'>('create')

// Delete confirmation state
const deleteConfirmOpen = ref(false)
const workerToDelete = ref<Worker | null>(null)

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
} = useDataGrid(loadData)

async function loadData() {
  loading.value = true
  try {
    const response = await fetchWorkers({
      page: currentPage.value,
      perPage: itemsPerPage.value,
      sortBy: sortField.value,
      sortOrder: sortDirection.value,
    })
    workers.value = response.data
    totalPages.value = response.meta.totalPages
    totalItems.value = response.meta.totalItems
  } catch (error) {
    console.error('Failed to load workers:', error)
  } finally {
    loading.value = false
  }
}

// Open modal for creating new worker
const handleAddWorker = () => {
  selectedWorker.value = null
  workerModalMode.value = 'create'
  workerModalOpen.value = true
}

// Open modal for editing worker
const handleEditWorker = (worker: Worker) => {
  selectedWorker.value = worker
  workerModalMode.value = 'edit'
  workerModalOpen.value = true
}

// Open delete confirmation
const handleDeleteWorker = (worker: Worker) => {
  workerToDelete.value = worker
  deleteConfirmOpen.value = true
}

// Save worker (create or update)
const handleSaveWorker = async (data: Omit<Worker, 'id'> | Partial<Worker>) => {
  try {
    if (workerModalMode.value === 'create') {
      await createWorker(data as Omit<Worker, 'id'>)
    } else if (selectedWorker.value) {
      await updateWorker(selectedWorker.value.id, data)
    }
    workerModalOpen.value = false
    await loadData()
  } catch (error) {
    console.error('Failed to save worker:', error)
  }
}

// Confirm delete
const handleConfirmDelete = async () => {
  if (!workerToDelete.value) return

  try {
    await deleteWorker(workerToDelete.value.id)
    deleteConfirmOpen.value = false
    workerToDelete.value = null
    await loadData()
  } catch (error) {
    console.error('Failed to delete worker:', error)
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <div>
    <!-- Header with Action Button -->
    <div class="mb-3">
      <div class="flex items-center justify-between mb-2">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Workers</h3>
        <Button variant="primary" @click="handleAddWorker">+ Worker</Button>
      </div>
    </div>

    <!-- Workers Table -->
    <Card>
      <DataGrid
        :data="workers"
        :columns="[
          { key: 'name', label: 'Name', sortable: true },
          { key: 'url', label: 'URL', sortable: true },
          { key: 'headers', label: 'Headers', sortable: false },
          { key: 'actions', label: '', sortable: false },
        ]"
        :loading="loading"
        :current-page="currentPage"
        :items-per-page="itemsPerPage"
        :total-pages="totalPages"
        :total-items="totalItems"
        :sort-field="sortField"
        :sort-direction="sortDirection"
        @page-change="handlePageChange"
        @per-page-change="handlePerPageChange"
        @sort="handleSort"
      >
        <!-- Name Column -->
        <template #cell-name="{ row }">
          <span class="font-medium text-gray-900 dark:text-white">{{ (row as Worker).name }}</span>
        </template>

        <!-- URL Column -->
        <template #cell-url="{ row }">
          <a
            :href="(row as Worker).url"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-1 text-primary-600 hover:underline dark:text-primary-500"
          >
            {{ (row as Worker).url }}
            <svg
              class="h-3 w-3"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
              />
            </svg>
          </a>
        </template>

        <!-- Headers Column -->
        <template #cell-headers="{ row }">
          <div class="text-xs space-y-1">
            <div v-for="(value, key) in (row as Worker).headers" :key="key">
              <span class="font-medium">{{ key }}:</span> {{ value }}
            </div>
            <div v-if="Object.keys((row as Worker).headers).length === 0" class="text-gray-400">
              No headers
            </div>
          </div>
        </template>

        <!-- Actions Column -->
        <template #cell-actions="{ row }">
          <div class="flex items-center gap-2 justify-end">
            <button
              type="button"
              @click="handleEditWorker(row as Worker)"
              class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              title="Edit"
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
              <span class="sr-only">Edit</span>
            </button>
            <button
              type="button"
              @click="handleDeleteWorker(row as Worker)"
              class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
              title="Delete"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                height="24px"
                viewBox="0 -960 960 960"
                width="24px"
                fill="currentColor"
              >
                <path
                  d="M292.31-140q-29.92 0-51.12-21.19Q220-182.39 220-212.31V-720h-40v-60h180v-35.38h240V-780h180v60h-40v507.69Q740-182 719-161q-21 21-51.31 21H292.31ZM680-720H280v507.69q0 5.39 3.46 8.85t8.85 3.46h375.38q4.62 0 8.46-3.85 3.85-3.84 3.85-8.46V-720ZM376.16-280h59.99v-360h-59.99v360Zm147.69 0h59.99v-360h-59.99v360ZM280-720v520-520Z"
                />
              </svg>
              <span class="sr-only">Delete</span>
            </button>
          </div>
        </template>
      </DataGrid>
    </Card>

    <!-- Worker Modal -->
    <WorkerModal
      v-model="workerModalOpen"
      :worker="selectedWorker"
      :mode="workerModalMode"
      @save="handleSaveWorker"
    />

    <!-- Delete Confirmation -->
    <Confirm
      v-model="deleteConfirmOpen"
      id="delete-worker-confirm"
      confirm-variant="danger"
      confirm-text="Yes, delete"
      cancel-text="Cancel"
      @confirm="handleConfirmDelete"
      @cancel="deleteConfirmOpen = false"
    >
      <p class="text-gray-500 dark:text-gray-400">
        Are you sure you want to delete this worker?
      </p>
    </Confirm>
  </div>
</template>

