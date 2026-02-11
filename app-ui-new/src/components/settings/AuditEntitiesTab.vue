<script setup lang="ts">
import { ref, onMounted } from 'vue'
import Button from '@/components/ui/Button.vue'
import Confirm from '@/components/ui/Confirm.vue'
import EntityModal from '@/components/settings/EntityModal.vue'
import {
  fetchAuditEntities,
  createEntity,
  updateEntity,
  deleteEntity,
} from '@/services/auditEntitiesService'
import type { AuditEntity } from '@/types/settings'

const entities = ref<AuditEntity[]>([])
const loading = ref(false)

// Entity modal state
const entityModalOpen = ref(false)
const selectedEntity = ref<AuditEntity | null>(null)
const entityModalMode = ref<'create' | 'edit'>('create')

// Delete confirmation state
const deleteConfirmOpen = ref(false)
const entityToDelete = ref<AuditEntity | null>(null)

async function loadData() {
  loading.value = true
  try {
    entities.value = await fetchAuditEntities()
  } catch (error) {
    console.error('Failed to load entities:', error)
  } finally {
    loading.value = false
  }
}

// Open modal for creating new entity
const handleAddEntity = () => {
  selectedEntity.value = null
  entityModalMode.value = 'create'
  entityModalOpen.value = true
}

// Open modal for editing entity
const handleEditEntity = (entity: AuditEntity) => {
  selectedEntity.value = entity
  entityModalMode.value = 'edit'
  entityModalOpen.value = true
}

// Open delete confirmation
const handleDeleteEntity = (entity: AuditEntity) => {
  entityToDelete.value = entity
  deleteConfirmOpen.value = true
}

// Save entity (create or update)
const handleSaveEntity = async (data: Omit<AuditEntity, 'id'> | Partial<AuditEntity>) => {
  try {
    if (entityModalMode.value === 'create') {
      await createEntity(data as Omit<AuditEntity, 'id'>)
    } else if (selectedEntity.value) {
      await updateEntity(selectedEntity.value.id, data)
    }
    entityModalOpen.value = false
    await loadData()
  } catch (error) {
    console.error('Failed to save entity:', error)
  }
}

// Confirm delete
const handleConfirmDelete = async () => {
  if (!entityToDelete.value) return

  try {
    await deleteEntity(entityToDelete.value.id)
    deleteConfirmOpen.value = false
    entityToDelete.value = null
    await loadData()
  } catch (error) {
    console.error('Failed to delete entity:', error)
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <div class="space-y-4">
    <!-- Header with Action Button -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Audit Entities</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Manage audit entities for tracking data changes
        </p>
      </div>
      <Button variant="primary" @click="handleAddEntity">
        <svg
          class="h-4 w-4 mr-2"
          fill="none"
          stroke="currentColor"
          stroke-width="1.5"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Entity
      </Button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div
        class="h-8 w-8 animate-spin rounded-full border-4 border-gray-300 border-t-primary-600 dark:border-gray-600 dark:border-t-primary-500"
      ></div>
    </div>

    <!-- Entity Cards -->
    <div v-else-if="entities.length > 0" class="space-y-4">
      <div
        v-for="entity in entities"
        :key="entity.id"
        class="rounded-lg bg-white p-6 shadow dark:bg-gray-800"
      >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div class="flex-1">
            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
              Entity name
            </label>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ entity.name }}
            </h3>
            <div class="mt-4">
              <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                Attributes
              </label>
              <div class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                <div
                  v-for="(attr, index) in entity.attributes"
                  :key="index"
                  class="flex flex-col gap-0.5 sm:flex-row sm:items-center sm:gap-2"
                >
                  <span class="font-medium text-gray-900 dark:text-white">{{ attr.name }}:</span>
                  <span class="text-gray-600 dark:text-gray-300">{{ attr.description }}</span>
                </div>
              </div>
            </div>
          </div>
          <div class="flex flex-wrap gap-2">
            <button
              type="button"
              @click="handleEditEntity(entity)"
              class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-primary-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-primary-900"
            >
              <svg
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M16.862 3.487a2.25 2.25 0 0 1 3.182 3.182l-9.8 9.8a4.5 4.5 0 0 1-1.897 1.128l-3.356.957a.75.75 0 0 1-.918-.918l.957-3.356a4.5 4.5 0 0 1 1.128-1.897l9.8-9.8Z"
                />
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5 13.5 4.5" />
              </svg>
              Edit
            </button>
            <button
              type="button"
              @click="handleDeleteEntity(entity)"
              class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 dark:focus:ring-primary-900"
            >
              <svg
                class="h-4 w-4"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M19 7H5m14 0-1 12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 7m14 0H5m3 0V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m-5 5v6m4-6v6"
                />
              </svg>
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="rounded-lg bg-white p-12 text-center shadow dark:bg-gray-800">
      <p class="text-gray-500 dark:text-gray-400">No entities found. Create your first entity to get started.</p>
    </div>

    <!-- Entity Modal -->
    <EntityModal
      v-model="entityModalOpen"
      :entity="selectedEntity"
      :mode="entityModalMode"
      @save="handleSaveEntity"
    />

    <!-- Delete Confirmation -->
    <Confirm
      v-model="deleteConfirmOpen"
      id="delete-entity-confirm"
      confirm-variant="danger"
      confirm-text="Yes, delete"
      cancel-text="Cancel"
      @confirm="handleConfirmDelete"
      @cancel="deleteConfirmOpen = false"
    >
      <p class="text-gray-500 dark:text-gray-400">
        Are you sure you want to delete this entity?
      </p>
    </Confirm>
  </div>
</template>

