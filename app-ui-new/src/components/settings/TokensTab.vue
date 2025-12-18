<script setup lang="ts">
import { ref, onMounted } from 'vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import Card from '@/components/ui/Card.vue'
import Button from '@/components/ui/Button.vue'
import Confirm from '@/components/ui/Confirm.vue'
import TokenModal from '@/components/settings/TokenModal.vue'
import ViewTokenModal from '@/components/settings/ViewTokenModal.vue'
import { useDataGrid } from '@/composables/useDataGrid'
import {
  fetchTokens,
  createToken,
  deleteToken,
  fetchAvailableScopes,
} from '@/services/tokensService'
import type { Token, TokenScope } from '@/types/settings'

const tokens = ref<Token[]>([])
const availableScopes = ref<TokenScope[]>([])

// Token modal state
const tokenModalOpen = ref(false)

// View token modal state
const viewTokenModalOpen = ref(false)
const generatedToken = ref<Token | null>(null)

// Delete confirmation state
const deleteConfirmOpen = ref(false)
const tokenToDelete = ref<Token | null>(null)

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
    const response = await fetchTokens({
      page: currentPage.value,
      perPage: itemsPerPage.value,
      sortBy: sortField.value,
      sortOrder: sortDirection.value,
    })
    tokens.value = response.data
    totalPages.value = response.meta.totalPages
    totalItems.value = response.meta.totalItems
  } catch (error) {
    console.error('Failed to load tokens:', error)
  } finally {
    loading.value = false
  }
}

// Load available scopes
const loadScopes = async () => {
  try {
    availableScopes.value = await fetchAvailableScopes()
  } catch (error) {
    console.error('Failed to load scopes:', error)
  }
}

// Open modal for creating new token
const handleAddToken = () => {
  tokenModalOpen.value = true
}

// Open view token modal (for existing token - shows placeholder)
const handleViewToken = (token: Token) => {
  // For existing tokens, we don't have the tokenValue
  // In a real app, this might trigger a re-generation or show an error
  generatedToken.value = {
    ...token,
    tokenValue: '••••••••••••••••••••••••••••••••',
  }
  viewTokenModalOpen.value = true
}

// Open delete confirmation
const handleDeleteToken = (token: Token) => {
  tokenToDelete.value = token
  deleteConfirmOpen.value = true
}

// Generate token
const handleGenerateToken = async (data: {
  name: string
  expiration: string | null
  scopes: string[]
}) => {
  try {
    const newToken = await createToken(data)
    tokenModalOpen.value = false
    
    // Show the generated token in the view modal
    generatedToken.value = newToken
    viewTokenModalOpen.value = true
    
    // Reload the list
    await loadData()
  } catch (error) {
    console.error('Failed to generate token:', error)
  }
}

// Confirm delete
const handleConfirmDelete = async () => {
  if (!tokenToDelete.value) return

  try {
    await deleteToken(tokenToDelete.value.id)
    deleteConfirmOpen.value = false
    tokenToDelete.value = null
    await loadData()
  } catch (error) {
    console.error('Failed to delete token:', error)
  }
}

// Format date
const formatDate = (dateString: string | null) => {
  if (!dateString) return 'No expiration'
  return new Date(dateString).toISOString().split('T')[0]
}

onMounted(() => {
  loadData()
  loadScopes()
})
</script>

<template>
  <div>
    <!-- Header with Action Button -->
    <div class="mb-3">
      <div class="flex items-center justify-between mb-2">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tokens</h3>
        <Button variant="primary" @click="handleAddToken">+ Token</Button>
      </div>
    </div>

    <!-- Tokens Table -->
    <Card>
      <DataGrid
        :data="tokens"
        :columns="[
          { key: 'name', label: 'Name', sortable: true },
          { key: 'created', label: 'Created', sortable: true },
          { key: 'expiration', label: 'Expiration', sortable: true },
          { key: 'scopes', label: 'Scopes', sortable: false },
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
          <span class="font-medium text-gray-900 dark:text-white">{{ (row as Token).name }}</span>
        </template>

        <!-- Created Column -->
        <template #cell-created="{ row }">
          {{ formatDate((row as Token).created) }}
        </template>

        <!-- Expiration Column -->
        <template #cell-expiration="{ row }">
          <span :class="(row as Token).expiration ? '' : 'text-gray-400 dark:text-gray-500'">
            {{ formatDate((row as Token).expiration) }}
          </span>
        </template>

        <!-- Scopes Column -->
        <template #cell-scopes="{ row }">
          <div class="flex flex-wrap gap-1">
            <span
              v-for="scope in (row as Token).scopes"
              :key="scope"
              class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded dark:bg-blue-800 dark:text-blue-300"
            >
              {{ scope }}
            </span>
          </div>
        </template>

        <!-- Actions Column -->
        <template #cell-actions="{ row }">
          <div class="flex items-center justify-end gap-1">
            <button
              type="button"
              @click="handleViewToken(row as Token)"
              title="Copy Token"
              class="inline-flex items-center rounded-lg p-1 text-center text-sm font-medium text-gray-500 hover:bg-gray-200 hover:text-gray-900 focus:outline-none dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            >
              <svg
                class="h-5 w-5"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                height="24px"
                viewBox="0 -960 960 960"
                width="24px"
                fill="currentColor"
              >
                <path
                  d="M360-240q-33 0-56.5-23.5T280-320v-480q0-33 23.5-56.5T360-880h360q33 0 56.5 23.5T800-800v480q0 33-23.5 56.5T720-240H360Zm0-80h360v-480H360v480ZM200-80q-33 0-56.5-23.5T120-160v-560h80v560h440v80H200Zm160-240v-480 480Z"
                />
              </svg>
              <span class="sr-only">Copy Token</span>
            </button>
            <button
              type="button"
              @click="handleDeleteToken(row as Token)"
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

    <!-- Token Modal -->
    <TokenModal
      v-model="tokenModalOpen"
      :available-scopes="availableScopes"
      @generate="handleGenerateToken"
    />

    <!-- View Token Modal -->
    <ViewTokenModal v-model="viewTokenModalOpen" :token="generatedToken" />

    <!-- Delete Confirmation -->
    <Confirm
      v-model="deleteConfirmOpen"
      id="delete-token-confirm"
      confirm-variant="danger"
      confirm-text="Yes, delete"
      cancel-text="Cancel"
      @confirm="handleConfirmDelete"
      @cancel="deleteConfirmOpen = false"
    >
      <p class="text-gray-500 dark:text-gray-400">
        Are you sure you want to delete this token? This action cannot be undone.
      </p>
    </Confirm>
  </div>
</template>

