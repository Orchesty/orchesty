<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import Card from '@/components/ui/Card.vue'
import Confirm from '@/components/ui/Confirm.vue'
import Checkbox from '@/components/ui/Checkbox.vue'
import Button from '@/components/ui/Button.vue'
import MoreActions from '@/components/ui/MoreActions.vue'
import type { MoreActionsSection } from '@/components/ui/MoreActions.vue'
import { useToast } from '@/composables/useToast'
import { useCloudLimitsUsage } from '@/composables/useCloudLimitsUsage'
import { fetchRunningBridges, decommissionBridge, restartBridge } from '@/services/resourcesService'
import { deleteTopology } from '@/services/topologiesService'
import type { BridgeItem, BridgesSummary } from '@/services/resourcesService'
import type { TableColumn } from '@/types/dashboard'
import { Server, AlertTriangle, ArrowDown, RefreshCw, Loader2 } from 'lucide-vue-next'

const { showToast } = useToast()
const { usage: limitsUsage } = useCloudLimitsUsage()

const slotLimit = computed<number | null>(() => {
  const limit = limitsUsage.value?.limits.topologySlots
  return typeof limit === 'number' && limit > 0 ? limit : null
})

const slotUsageLabel = computed(() => {
  const total = summary.value.total
  return slotLimit.value !== null ? `Used ${total} / ${slotLimit.value}` : `Used ${total} (unlimited plan)`
})

const allBridges = ref<BridgeItem[]>([])
const summary = ref<BridgesSummary>({ total: 0, reducible: 0 })
const loading = ref(true)
const highlightReducible = ref(false)

const currentPage = ref(1)
const itemsPerPage = ref(25)
const sortField = ref('name')
const sortDirection = ref<'asc' | 'desc'>('asc')

const confirmOpen = ref(false)
const deleteConfirmOpen = ref(false)
const selectedBridge = ref<BridgeItem | null>(null)
const confirmChecked = ref(false)
const decommissioning = ref(false)
const deleting = ref(false)
const restartingAll = ref(false)
const restartingIds = ref(new Set<string>())
const restartProgress = ref<{ current: number; total: number; name: string } | null>(null)

const hasActiveResources = computed(() => {
  if (!selectedBridge.value) return false
  return selectedBridge.value.runningProcesses > 0 || selectedBridge.value.trashCount > 0
})

const reducibleIds = computed(() => {
  const grouped: Record<string, BridgeItem[]> = {}
  for (const b of allBridges.value) {
    ;(grouped[b.name] ??= []).push(b)
  }
  const ids = new Set<string>()
  for (const versions of Object.values(grouped)) {
    if (versions.length <= 1) continue
    const maxVersion = Math.max(...versions.map((v) => v.version))
    for (const v of versions) {
      if (v.version < maxVersion) ids.add(v._id)
    }
  }
  return ids
})

function isReducible(bridge: BridgeItem): boolean {
  return reducibleIds.value.has(bridge._id)
}

function getRowClass(row: Record<string, unknown>): string {
  const bridge = row as unknown as BridgeItem
  if (highlightReducible.value && isReducible(bridge)) {
    return 'bg-amber-50 dark:bg-amber-950/30'
  }
  return 'bg-white dark:bg-gray-800'
}

const sortedBridges = computed(() => {
  const field = sortField.value as keyof BridgeItem
  const dir = sortDirection.value === 'asc' ? 1 : -1
  return [...allBridges.value].sort((a, b) => {
    const va = a[field]
    const vb = b[field]
    if (typeof va === 'string' && typeof vb === 'string') return va.localeCompare(vb) * dir
    if (typeof va === 'number' && typeof vb === 'number') return (va - vb) * dir
    return 0
  })
})

const totalPages = computed(() => Math.ceil(sortedBridges.value.length / itemsPerPage.value) || 1)

const paginatedBridges = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  return sortedBridges.value.slice(start, start + itemsPerPage.value)
})

const columns: TableColumn[] = [
  { key: 'name', label: 'Topology', sortable: true },
  { key: 'version', label: 'Version', sortable: true },
  { key: 'enabled', label: 'Status', sortable: false },
  { key: 'runningProcesses', label: 'Running Processes', sortable: true },
  { key: 'trashCount', label: 'Trash Messages', sortable: true },
  { key: 'actions', label: '', sortable: false, className: 'text-right w-40' },
]

function handlePageChange(page: number) {
  currentPage.value = page
}

function handlePerPageChange(perPage: number) {
  itemsPerPage.value = perPage
  currentPage.value = 1
}

function handleSort(config: { field: string; direction: 'asc' | 'desc' }) {
  sortField.value = config.field
  sortDirection.value = config.direction
  currentPage.value = 1
}

async function loadData() {
  loading.value = true
  try {
    const response = await fetchRunningBridges()
    allBridges.value = response.items
    summary.value = response.summary
  } catch (err) {
    console.error('Failed to load bridges:', err)
    showToast('Failed to load bridges', 'error')
  } finally {
    loading.value = false
  }
}

async function handleRestartAllEnabled() {
  const enabledBridges = allBridges.value.filter((b) => b.enabled)
  if (enabledBridges.length === 0) {
    showToast('No enabled bridges to restart', 'info')
    return
  }
  restartingAll.value = true
  let succeeded = 0
  let failed = 0
  for (let i = 0; i < enabledBridges.length; i++) {
    const bridge = enabledBridges[i]!
    restartProgress.value = { current: i + 1, total: enabledBridges.length, name: bridge.name }
    restartingIds.value.add(bridge._id)
    try {
      await restartBridge(bridge._id)
      succeeded++
    } catch {
      failed++
    } finally {
      restartingIds.value.delete(bridge._id)
    }
  }
  restartProgress.value = null
  restartingAll.value = false
  if (failed === 0) {
    showToast(`All ${succeeded} enabled bridges restarted`, 'success')
  } else {
    showToast(`Restarted ${succeeded}, failed ${failed}`, 'error')
  }
}

async function handleRestartBridge(bridge: BridgeItem) {
  restartingIds.value.add(bridge._id)
  try {
    await restartBridge(bridge._id)
    showToast(`Bridge for ${bridge.name} v${bridge.version} has been restarted`, 'success')
  } catch (err) {
    console.error('Failed to restart bridge:', err)
    showToast('Failed to restart bridge', 'error')
  } finally {
    restartingIds.value.delete(bridge._id)
  }
}

function getActionsForBridge(bridge: BridgeItem): MoreActionsSection[] {
  return [
    {
      items: [
        {
          type: 'button',
          label: 'Restart topology',
          onClick: () => handleRestartBridge(bridge),
        },
      ],
    },
    {
      items: [
        {
          type: 'button',
          label: 'Decommission bridge',
          onClick: () => handleDecommission(bridge),
        },
        {
          type: 'button',
          label: 'Delete topology',
          onClick: () => handleDeleteTopology(bridge),
        },
      ],
    },
  ]
}

function handleDecommission(bridge: BridgeItem) {
  selectedBridge.value = bridge
  confirmChecked.value = false
  confirmOpen.value = true
}

function handleDeleteTopology(bridge: BridgeItem) {
  selectedBridge.value = bridge
  deleteConfirmOpen.value = true
}

async function handleConfirmDeleteTopology() {
  if (!selectedBridge.value) return
  deleting.value = true
  try {
    await decommissionBridge(selectedBridge.value._id, true)
    await deleteTopology(selectedBridge.value._id)
    showToast(
      `Topology ${selectedBridge.value.name} v${selectedBridge.value.version} has been deleted`,
      'success',
    )
    deleteConfirmOpen.value = false
    await loadData()
  } catch (err) {
    console.error('Failed to delete topology:', err)
    showToast('Failed to delete topology', 'error')
  } finally {
    deleting.value = false
  }
}

async function handleConfirmDecommission() {
  if (!selectedBridge.value) return
  if (hasActiveResources.value && !confirmChecked.value) {
    return
  }
  decommissioning.value = true
  try {
    await decommissionBridge(selectedBridge.value._id, hasActiveResources.value)
    showToast(
      `Bridge for ${selectedBridge.value.name} v${selectedBridge.value.version} has been decommissioned`,
      'success',
    )
    confirmOpen.value = false
    await loadData()
  } catch (err) {
    console.error('Failed to decommission bridge:', err)
    showToast('Failed to decommission bridge', 'error')
  } finally {
    decommissioning.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <main class="h-full overflow-y-auto"><div class="px-4 pb-4 pt-6">
    <div class="mb-6 flex items-start justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Resources</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Overview of running bridge containers and their resources
        </p>
      </div>
      <div v-if="allBridges.some((b) => b.enabled)" class="flex flex-col items-end gap-1">
        <Button
          variant="outline"
          :disabled="restartingAll"
          @click="handleRestartAllEnabled"
        >
          <RefreshCw class="-ms-0.5 me-1.5 h-4 w-4" :class="{ 'animate-spin': restartingAll }" />
          {{ restartingAll ? 'Restarting...' : 'Restart all enabled' }}
        </Button>
        <p v-if="restartProgress" class="text-xs text-gray-500 dark:text-gray-400">
          {{ restartProgress.current }}/{{ restartProgress.total }}: {{ restartProgress.name }}
        </p>
      </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-2 max-w-xl">
      <Card>
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-100 dark:bg-primary-900/30">
            <Server class="h-5 w-5 text-primary-600 dark:text-primary-400" :stroke-width="1.8" />
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Topology slots</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ summary.total }}</p>
            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ slotUsageLabel }}</p>
          </div>
        </div>
      </Card>
      <Card>
        <div class="flex items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg" :class="summary.reducible > 0 ? 'bg-amber-100 dark:bg-amber-900/30' : 'bg-gray-100 dark:bg-gray-700'">
              <AlertTriangle class="h-5 w-5" :class="summary.reducible > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400 dark:text-gray-500'" :stroke-width="1.8" />
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Reducible</p>
              <p class="text-2xl font-bold" :class="summary.reducible > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white'">{{ summary.reducible }}</p>
            </div>
          </div>
          <Button
            v-if="summary.reducible > 0"
            :variant="highlightReducible ? 'primary' : 'outline'"
            @click="highlightReducible = !highlightReducible"
          >
            <ArrowDown class="-ms-0.5 me-1.5 h-4 w-4" />
            {{ highlightReducible ? 'Clear' : 'Show' }}
          </Button>
        </div>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
          Older versions still holding a topology slot. A newer version of the same topology is published, so these can be safely decommissioned to free their slot.
        </p>
      </Card>
    </div>

    <Card>
      <DataGrid
        :columns="columns"
        :data="paginatedBridges"
        :current-page="currentPage"
        :total-pages="totalPages"
        :total-items="sortedBridges.length"
        :items-per-page="itemsPerPage"
        :loading="loading"
        :sort-field="sortField"
        :sort-direction="sortDirection"
        :row-class="getRowClass"
        @page-change="handlePageChange"
        @per-page-change="handlePerPageChange"
        @sort="handleSort"
      >
        <template #cell-name="{ row }">
          <span class="inline-flex items-center gap-1.5 font-medium text-gray-900 dark:text-white">
            <AlertTriangle
              v-if="highlightReducible && isReducible(row as BridgeItem)"
              class="h-4 w-4 shrink-0 text-amber-500"
              :stroke-width="2"
            />
            {{ (row as BridgeItem).name }}
          </span>
        </template>

        <template #cell-version="{ row }">
          <span class="text-gray-700 dark:text-gray-300">v{{ (row as BridgeItem).version }}</span>
        </template>

        <template #cell-enabled="{ row }">
          <span
            v-if="(row as BridgeItem).enabled"
            class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400"
          >
            Enabled
          </span>
          <span
            v-else
            class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300"
          >
            Disabled
          </span>
        </template>

        <template #cell-runningProcesses="{ row }">
          <span
            :class="(row as BridgeItem).runningProcesses > 0
              ? 'font-semibold text-primary-600 dark:text-primary-400'
              : 'text-gray-500 dark:text-gray-400'"
          >
            {{ (row as BridgeItem).runningProcesses }}
          </span>
        </template>

        <template #cell-trashCount="{ row }">
          <span
            :class="(row as BridgeItem).trashCount > 0
              ? 'font-semibold text-amber-600 dark:text-amber-400'
              : 'text-gray-500 dark:text-gray-400'"
          >
            {{ (row as BridgeItem).trashCount }}
          </span>
        </template>

        <template #cell-actions="{ row }">
          <div class="flex items-center justify-end gap-1">
            <Loader2
              v-if="restartingIds.has((row as BridgeItem)._id)"
              class="h-5 w-5 animate-spin text-primary-500"
            />
            <MoreActions
              :id="`bridge-actions-${(row as BridgeItem)._id}`"
              :sections="getActionsForBridge(row as BridgeItem)"
            />
          </div>
        </template>
      </DataGrid>
    </Card>

    <Confirm
      v-model="confirmOpen"
      id="decommission-bridge-confirm"
      :confirm-text="decommissioning ? 'Decommissioning...' : 'Decommission'"
      confirm-variant="danger"
      size="lg"
      @confirm="handleConfirmDecommission"
    >
      <template v-if="selectedBridge">
        <template v-if="!hasActiveResources">
          <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Decommission Bridge</h3>
          <p class="text-gray-500 dark:text-gray-400">
            Bridge for topology <strong>{{ selectedBridge.name }} v{{ selectedBridge.version }}</strong> will be stopped and removed. Continue?
          </p>
        </template>

        <template v-else>
          <AlertTriangle class="mx-auto mb-3 h-12 w-12 text-amber-500" :stroke-width="1.5" />
          <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Decommission Bridge</h3>
          <p class="mb-4 text-gray-500 dark:text-gray-400">
            Bridge for <strong>{{ selectedBridge.name }} v{{ selectedBridge.version }}</strong> has active resources:
          </p>
          <ul class="mb-4 space-y-1.5 text-left text-sm text-gray-600 dark:text-gray-400">
            <li v-if="selectedBridge.runningProcesses > 0" class="flex items-center gap-2">
              <span class="inline-block h-1.5 w-1.5 rounded-full bg-red-500"></span>
              <strong>{{ selectedBridge.runningProcesses }}</strong> running processes (will be marked as failed)
            </li>
            <li v-if="selectedBridge.trashCount > 0" class="flex items-center gap-2">
              <span class="inline-block h-1.5 w-1.5 rounded-full bg-amber-500"></span>
              <strong>{{ selectedBridge.trashCount }}</strong> messages in trash (will be deleted)
            </li>
            <li class="flex items-center gap-2">
              <span class="inline-block h-1.5 w-1.5 rounded-full bg-gray-400"></span>
              Limiter/repeater messages will be removed
            </li>
            <li class="flex items-center gap-2">
              <span class="inline-block h-1.5 w-1.5 rounded-full bg-gray-400"></span>
              RabbitMQ queues will be deleted
            </li>
          </ul>
          <div class="mb-2">
            <Checkbox
              v-model="confirmChecked"
              label="I understand that all data will be lost"
            />
          </div>
        </template>
      </template>
    </Confirm>

    <Confirm
      v-model="deleteConfirmOpen"
      id="delete-topology-confirm"
      :confirm-text="deleting ? 'Deleting...' : 'Delete topology'"
      confirm-variant="danger"
      size="lg"
      @confirm="handleConfirmDeleteTopology"
    >
      <template v-if="selectedBridge">
        <AlertTriangle class="mx-auto mb-3 h-12 w-12 text-red-500" :stroke-width="1.5" />
        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Delete Topology</h3>
        <p class="mb-4 text-gray-500 dark:text-gray-400">
          Topology <strong>{{ selectedBridge.name }} v{{ selectedBridge.version }}</strong> will be decommissioned and permanently deleted. This action cannot be undone.
        </p>
        <ul v-if="hasActiveResources" class="mb-4 space-y-1.5 text-left text-sm text-gray-600 dark:text-gray-400">
          <li v-if="selectedBridge.runningProcesses > 0" class="flex items-center gap-2">
            <span class="inline-block h-1.5 w-1.5 rounded-full bg-red-500"></span>
            <strong>{{ selectedBridge.runningProcesses }}</strong> running processes (will be marked as failed)
          </li>
          <li v-if="selectedBridge.trashCount > 0" class="flex items-center gap-2">
            <span class="inline-block h-1.5 w-1.5 rounded-full bg-amber-500"></span>
            <strong>{{ selectedBridge.trashCount }}</strong> messages in trash (will be deleted)
          </li>
        </ul>
      </template>
    </Confirm>
  </div></main>
</template>
