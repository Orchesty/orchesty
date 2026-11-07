<script setup lang="ts">
import { computed, ref } from 'vue'
import Card from '@/components/ui/Card.vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import QuickFilter from '@/components/ui/datagrid/QuickFilter.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import ConnectorDetailDrawer from '@/components/dashboard/ConnectorDetailDrawer.vue'
import { actionNodeId, fetchActions, fetchActionStats, type ActionRow } from '@/services/actionsService'
import type { Connector } from '@/types/connectors'
import type { TableColumn, TimeFilter } from '@/types/dashboard'
import type { ActionConfig, QuickFilterOption } from '@/types/datagrid'
import { useDataGrid } from '@/composables/useDataGrid'
import { convertTimeFilterToDateTimeRange, formatDateTimeForApi } from '@/utils/timeRangeConverter'

interface Props {
  /** Extra drawer tabs contributed by the enterprise edition */
  extraTabs?: { id: string; label: string }[]
}

withDefaults(defineProps<Props>(), {
  extraTabs: () => [],
})

const STATS_PERIOD: TimeFilter = '7d'

const allRows = ref<ActionRow[]>([])
const rows = ref<ActionRow[]>([])
const kindFilter = ref<'all' | 'read' | 'write'>('all')
const selected = ref<ActionRow | null>(null)
const drawerOpen = ref(false)

// The drawer is the connector detail (same statistics, chart and error records); an action is a
// connector without a topology whose metrics use node id `action:<key>`.
const selectedConnector = computed<Connector | null>(() => {
  const row = selected.value
  if (!row) return null

  return {
    nodeIds: [actionNodeId(row.key)],
    name: row.name,
    application: row.app,
    topologyIds: [],
    avgRequestTime: row.avgRequestTime,
    requests: row.requests,
    errors400: row.errors400,
    errors500: row.errors500,
    lastRequestStatus: row.lastRequestStatus,
    status: row.requests > 0 ? (row.errors400 + row.errors500 > 0 ? 'errors' : 'ok') : 'none',
    type: 'action',
  }
})

const columns: TableColumn[] = [
  { key: 'app', label: 'Application', sortable: true },
  { key: 'name', label: 'Action', sortable: true },
  { key: 'kind', label: 'Type', sortable: true },
  { key: 'sdk', label: 'Worker', sortable: true },
  { key: 'requests', label: 'Requests (7 days)', sortable: true },
  { key: 'errors400', label: 'Status 400', sortable: true },
  { key: 'errors500', label: 'Status 500', sortable: true },
  { key: 'avgRequestTime', label: 'Avg request time', sortable: true },
]

const kindOptions: QuickFilterOption[] = [
  { value: 'all', label: 'All' },
  { value: 'read', label: 'Read' },
  { value: 'write', label: 'Write' },
]

const actions: ActionConfig[] = [
  {
    icon: 'search',
    title: 'View details',
    onClick: (row) => {
      selected.value = row as ActionRow
      drawerOpen.value = true
    },
  },
]

const fetchAll = async () => {
  const catalog = await fetchActions()
  const dateFrom = formatDateTimeForApi(convertTimeFilterToDateTimeRange(STATS_PERIOD).from) || undefined
  const stats = await fetchActionStats(catalog.map(a => a.key), dateFrom)
  allRows.value = catalog.map(a => ({ ...a, ...stats[a.key]! }))
}

const loadData = async (refetch = true) => {
  loading.value = true
  try {
    if (refetch || allRows.value.length === 0) {
      await fetchAll()
    }

    const filtered = allRows.value.filter(r => kindFilter.value === 'all' || r.kind === kindFilter.value)
    const field = (sortField.value || 'name') as keyof ActionRow
    const dir = sortDirection.value === 'desc' ? -1 : 1
    filtered.sort((a, b) => {
      const av = a[field] ?? ''
      const bv = b[field] ?? ''
      if (typeof av === 'number' && typeof bv === 'number') return (av - bv) * dir
      return String(av).localeCompare(String(bv)) * dir
    })

    totalItems.value = filtered.length
    totalPages.value = Math.max(1, Math.ceil(filtered.length / itemsPerPage.value))
    rows.value = filtered.slice((currentPage.value - 1) * itemsPerPage.value, currentPage.value * itemsPerPage.value)
  } catch (error) {
    console.error('Failed to load actions:', error)
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
  defaultSort: { field: 'name', direction: 'asc' },
  onDataLoad: () => loadData(false),
  filters: [kindFilter],
})

loadData(true)
</script>

<template>
  <main class="h-full overflow-y-auto">
    <div class="px-4 pb-4 pt-6">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Actions</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Connectors your workers call directly, without a topology. Orchesty authorizes and measures each call;
          the data goes straight to the target system.
        </p>
      </div>

      <Card>
        <DataGrid
          :columns="columns"
          :data="rows"
          :loading="loading"
          :current-page="currentPage"
          :total-pages="totalPages"
          :total-items="totalItems"
          :items-per-page="itemsPerPage"
          :sort-field="sortField"
          :sort-direction="sortDirection"
          :actions="actions"
          @page-change="handlePageChange"
          @per-page-change="handlePerPageChange"
          @sort="handleSort"
        >
          <template #quick-filters>
            <QuickFilter v-model="kindFilter" name="actions-kind" label="Show only:" :options="kindOptions" />
          </template>

          <template #cell-kind="{ value }">
            <StatusBadge :variant="value === 'write' ? 'yellow' : 'blue'">{{ value === 'write' ? 'Write' : 'Read' }}</StatusBadge>
          </template>

          <template #cell-requests="{ value }">
            <span v-if="value > 0" class="text-sm font-medium text-gray-900 dark:text-white">{{ value.toLocaleString() }}</span>
            <span v-else class="text-gray-400">-</span>
          </template>

          <template #cell-errors400="{ value }">
            <StatusBadge v-if="value > 0" variant="yellow">{{ value }}</StatusBadge>
            <span v-else class="text-gray-400">-</span>
          </template>

          <template #cell-errors500="{ value }">
            <StatusBadge v-if="value > 0" variant="red">{{ value }}</StatusBadge>
            <span v-else class="text-gray-400">-</span>
          </template>

          <template #cell-avgRequestTime="{ value }">
            <span v-if="value > 0" class="text-sm text-gray-900 dark:text-white">{{ value }} ms</span>
            <span v-else class="text-gray-400">-</span>
          </template>
        </DataGrid>
      </Card>
    </div>

    <ConnectorDetailDrawer
      v-model="drawerOpen"
      :connector="selectedConnector"
      :time-filter="STATS_PERIOD"
      :tabs="extraTabs"
      label="Action Details"
    >
      <template v-for="tab in extraTabs" :key="tab.id" #[`tab-${tab.id}`]>
        <slot v-if="selected" :name="`tab-${tab.id}`" :action="selected" />
      </template>
    </ConnectorDetailDrawer>
  </main>
</template>
