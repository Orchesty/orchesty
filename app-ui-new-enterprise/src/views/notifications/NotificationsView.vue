<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue'
import DataGrid from '@/components/ui/DataGrid.vue'
import Card from '@/components/ui/Card.vue'
import StatusBadge, { type BadgeVariant } from '@/components/ui/StatusBadge.vue'
import QuickFilter from '@/components/ui/datagrid/QuickFilter.vue'
import TextInput from '@/components/ui/datagrid/TextInput.vue'
import { getNotifications, type InAppNotificationItem, type NotificationFilters } from '@/services/inAppNotificationService'
import { useNotificationStream } from '@/composables/useNotificationStream'
import type { TableColumn } from '@/types/dashboard'

const { onNotification, resetUnreadCount } = useNotificationStream()

const notifications = ref<InAppNotificationItem[]>([])
const loading = ref(false)
const currentPage = ref(1)
const itemsPerPage = ref(20)
const totalItems = ref(0)
const sortField = ref('created_at')
const sortDirection = ref<'asc' | 'desc'>('desc')
const severityFilter = ref('all')
const searchQuery = ref('')

const severityOptions = [
  { value: 'all', label: 'All' },
  { value: 'info', label: 'Info' },
  { value: 'warning', label: 'Warning' },
  { value: 'danger', label: 'Critical' },
]

const columns: TableColumn[] = [
  { key: 'event_type', label: 'Type', sortable: true },
  { key: 'severity', label: 'Severity', sortable: true },
  { key: 'topology_name', label: 'Topology', sortable: true },
  { key: 'created_at', label: 'Timestamp', sortable: true },
  { key: 'message', label: 'Description' },
]

const totalPages = computed(() => Math.ceil(totalItems.value / itemsPerPage.value))

const fetchData = async () => {
  loading.value = true
  try {
    const filters: NotificationFilters = {
      page: currentPage.value,
      limit: itemsPerPage.value,
    }
    if (severityFilter.value !== 'all') {
      filters.severity = severityFilter.value
    }

    const result = await getNotifications(filters)
    notifications.value = result.data
    totalItems.value = result.total
  } catch (error) {
    console.error('Failed to fetch notifications:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  resetUnreadCount()
  fetchData()
})

watch([currentPage, itemsPerPage, severityFilter], fetchData)

onNotification(() => {
  fetchData()
})

const filteredNotifications = computed(() => {
  if (!searchQuery.value) return notifications.value

  const query = searchQuery.value.toLowerCase()
  return notifications.value.filter(n =>
    n.event_type.toLowerCase().includes(query) ||
    n.message.toLowerCase().includes(query) ||
    (n.topology_name ?? '').toLowerCase().includes(query)
  )
})

const getSeverityVariant = (severity: string): BadgeVariant => {
  if (severity === 'danger' || severity === 'critical') return 'red'
  if (severity === 'warning') return 'yellow'
  return 'blue'
}

const getSeverityLabel = (severity: string): string => {
  if (severity === 'danger' || severity === 'critical') return 'Critical'
  if (severity === 'warning') return 'Warning'
  return 'Info'
}

const formatTimestamp = (timestamp: string): string => {
  const date = new Date(timestamp)
  return date.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const formatRelativeTimestamp = (timestamp: string): string => {
  const seconds = Math.floor((Date.now() - new Date(timestamp).getTime()) / 1000)
  if (seconds < 60) return 'just now'
  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return `${minutes}m ago`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}h ago`
  const days = Math.floor(hours / 24)
  return `${days}d ago`
}

const handleSort = (config: { field: string; direction: 'asc' | 'desc' }) => {
  sortField.value = config.field
  sortDirection.value = config.direction
}

const handlePageChange = (page: number) => {
  currentPage.value = page
}

const handlePerPageChange = (perPage: number) => {
  itemsPerPage.value = perPage
  currentPage.value = 1
}
</script>

<template>
  <div class="relative h-full overflow-y-auto px-4 pt-6">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Alerts</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        View alerts and events from this instance
      </p>
    </div>

    <Card>
      <DataGrid
        :columns="columns"
        :data="filteredNotifications"
        :loading="loading"
        :current-page="currentPage"
        :total-pages="totalPages"
        :total-items="totalItems"
        :items-per-page="itemsPerPage"
        :sort-field="sortField"
        :sort-direction="sortDirection"
        @sort="handleSort"
        @page-change="handlePageChange"
        @per-page-change="handlePerPageChange"
      >
        <template #quick-filters>
          <QuickFilter
            v-model="severityFilter"
            name="severity"
            label="Show:"
            :options="severityOptions"
          />
        </template>

        <template #filters>
          <TextInput
            v-model="searchQuery"
            placeholder="Search notifications..."
          />
        </template>

        <template #cell-event_type="{ row }">
          <span class="font-medium text-gray-900 dark:text-white">
            {{ row.event_type }}
          </span>
        </template>

        <template #cell-severity="{ row }">
            <StatusBadge :variant="getSeverityVariant(row.severity)">
              {{ getSeverityLabel(row.severity) }}
            </StatusBadge>
          </template>

        <template #cell-topology_name="{ row }">
          <span class="text-gray-900 dark:text-white">
            {{ row.topology_name ?? '—' }}
          </span>
        </template>

        <template #cell-created_at="{ row }">
          <div>
            <span class="text-gray-900 dark:text-white">{{ formatTimestamp(row.created_at) }}</span>
            <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">
              ({{ formatRelativeTimestamp(row.created_at) }})
            </span>
          </div>
        </template>

        <template #cell-message="{ row }">
          <span class="text-gray-600 dark:text-gray-400">{{ row.message }}</span>
        </template>
      </DataGrid>
    </Card>
  </div>
</template>
