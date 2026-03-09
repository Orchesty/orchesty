<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import CopyValue from '@/components/ui/CopyValue.vue'
import Confirm from '@/components/ui/Confirm.vue'
import EditMessageModal from '@/components/trash/EditMessageModal.vue'
import { useToast } from '@/composables/useToast'
import { useDateFormat } from '@/composables/useDateFormat'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'
import {
  fetchTrashItems,
  approveTrashItem,
  rejectTrashItem,
  updateTrashItem,
  approveAllTrashItems,
  rejectAllTrashItems,
} from '@/services/trashService'
import type { TrashItem } from '@/types/trash'

interface Props {
  modelValue: boolean
  topologyId: string
  nodeId: string
  correlationId: string
  nodeName: string
  hideBulkActions?: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'update': []
}>()

const { showToast } = useToast()
const { formatDateTime } = useDateFormat()
const { getNodeName } = useTopologyNodeMappings()

const currentItem = ref<TrashItem | null>(null)
const totalCount = ref(0)
const loading = ref(false)
const actionLoading = ref(false)
const editModalOpen = ref(false)
const confirmRejectOpen = ref(false)
const confirmRejectAllOpen = ref(false)

const resultMessage = computed(() => {
  if (!currentItem.value) return ''
  return (currentItem.value.headers['result-message'] as string) || ''
})

const formatJson = (obj: Record<string, unknown>): string => {
  return JSON.stringify(obj, null, 2)
}

const loadFirstItem = async () => {
  loading.value = true
  try {
    const result = await fetchTrashItems({
      correlationId: props.correlationId,
      node: props.nodeId,
      topology: props.topologyId,
      perPage: 1,
      page: 1,
      sortBy: 'timestamp',
      sortOrder: 'asc',
    })
    totalCount.value = result.pagination.total
    if (result.data.length > 0) {
      currentItem.value = result.data[0]
    } else {
      currentItem.value = null
      handleClose()
    }
  } catch (err) {
    console.error('Failed to load failed message:', err)
    showToast('Failed to load failed message', 'error')
  } finally {
    loading.value = false
  }
}

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      loadFirstItem()
    } else {
      currentItem.value = null
      totalCount.value = 0
    }
  },
)

const handleApprove = async () => {
  if (!currentItem.value) return
  actionLoading.value = true
  try {
    await approveTrashItem(currentItem.value.id)
    showToast('Message approved', 'success')
    emit('update')
    await loadNextOrClose()
  } catch (err) {
    console.error('Failed to approve:', err)
    showToast('Failed to approve message', 'error')
  } finally {
    actionLoading.value = false
  }
}

const handleReject = () => {
  confirmRejectOpen.value = true
}

const handleConfirmReject = async () => {
  if (!currentItem.value) return
  confirmRejectOpen.value = false
  actionLoading.value = true
  try {
    await rejectTrashItem(currentItem.value.id)
    showToast('Message rejected', 'success')
    emit('update')
    await loadNextOrClose()
  } catch (err) {
    console.error('Failed to reject:', err)
    showToast('Failed to reject message', 'error')
  } finally {
    actionLoading.value = false
  }
}

const loadNextOrClose = async () => {
  const result = await fetchTrashItems({
    correlationId: props.correlationId,
    node: props.nodeId,
    topology: props.topologyId,
    perPage: 1,
    page: 1,
    sortBy: 'timestamp',
    sortOrder: 'asc',
  })
  if (result.data.length > 0) {
    totalCount.value = result.pagination.total
    currentItem.value = result.data[0]
  } else {
    handleClose()
  }
}

const handleApproveAll = async () => {
  actionLoading.value = true
  try {
    await approveAllTrashItems(props.topologyId, props.nodeId, props.correlationId)
    showToast('All failed messages approved', 'success')
    emit('update')
    handleClose()
  } catch (err) {
    console.error('Failed to approve all:', err)
    showToast('Failed to approve all messages', 'error')
  } finally {
    actionLoading.value = false
  }
}

const handleRejectAllClick = () => {
  confirmRejectAllOpen.value = true
}

const handleConfirmRejectAll = async () => {
  confirmRejectAllOpen.value = false
  actionLoading.value = true
  try {
    await rejectAllTrashItems(props.topologyId, props.nodeId, props.correlationId)
    showToast('All failed messages rejected', 'success')
    emit('update')
    handleClose()
  } catch (err) {
    console.error('Failed to reject all:', err)
    showToast('Failed to reject all messages', 'error')
  } finally {
    actionLoading.value = false
  }
}

const handleEdit = () => {
  editModalOpen.value = true
}

const handleSave = async (data: { headers: Record<string, unknown>; body: Record<string, unknown> }) => {
  if (!currentItem.value) return
  try {
    const updatedData = await updateTrashItem(currentItem.value.id, data)
    currentItem.value.headers = updatedData.headers
    currentItem.value.body = updatedData.body
    showToast('Message updated', 'success')
    editModalOpen.value = false
  } catch (err) {
    console.error('Failed to update:', err)
    showToast('Failed to update message', 'error')
  }
}

const handleSaveAndApprove = async (data: { headers: Record<string, unknown>; body: Record<string, unknown> }) => {
  if (!currentItem.value) return
  try {
    await updateTrashItem(currentItem.value.id, data)
    await approveTrashItem(currentItem.value.id)
    showToast('Message updated and approved', 'success')
    editModalOpen.value = false
    emit('update')
    await loadNextOrClose()
  } catch (err) {
    console.error('Failed to update and approve:', err)
    showToast('Failed to update and approve message', 'error')
  }
}

const handleClose = () => {
  emit('update:modelValue', false)
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="failed-message-modal"
    title="Failed Message"
    size="xl"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <svg
        class="h-8 w-8 animate-spin text-gray-400"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
      >
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
    </div>

    <!-- Message content -->
    <div v-else-if="currentItem" class="space-y-4">
      <!-- Counter + bulk actions -->
      <div v-if="!hideBulkActions" class="flex items-center gap-3">
        <div
          class="inline-flex items-center gap-2 rounded-full bg-red-100 px-3 py-1 text-xs font-medium text-red-700 dark:bg-red-900 dark:text-red-300"
        >
          <span>{{ totalCount }} failed message{{ totalCount !== 1 ? 's' : '' }}</span>
        </div>
        <template v-if="totalCount > 1">
          <button
            type="button"
            :disabled="actionLoading"
            class="text-xs font-medium text-primary-600 hover:text-primary-800 disabled:opacity-50 dark:text-primary-400 dark:hover:text-primary-300"
            @click="handleApproveAll"
          >
            Approve All ({{ totalCount }})
          </button>
          <button
            type="button"
            :disabled="actionLoading"
            class="text-xs font-medium text-red-600 hover:text-red-800 disabled:opacity-50 dark:text-red-400 dark:hover:text-red-300"
            @click="handleRejectAllClick"
          >
            Reject All ({{ totalCount }})
          </button>
        </template>
      </div>

      <!-- Attributes -->
      <div class="space-y-3">
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Node</label>
          <p class="text-sm text-gray-900 dark:text-white">{{ getNodeName(currentItem.nodeId) }}</p>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Correlation ID</label>
          <CopyValue :value="currentItem.correlationId">
            <span class="font-mono text-sm text-gray-900 dark:text-white">{{ currentItem.correlationId }}</span>
          </CopyValue>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Timestamp</label>
          <p class="text-sm text-gray-900 dark:text-white">{{ formatDateTime(currentItem.timestamp) }}</p>
        </div>
      </div>

      <!-- Result Message -->
      <div v-if="resultMessage">
        <h4 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Result Message</h4>
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-300">
          {{ resultMessage }}
        </div>
      </div>

      <!-- Headers -->
      <div>
        <h4 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Headers</h4>
        <div class="group/copy relative">
          <div class="absolute right-2 top-2 opacity-0 transition-opacity group-hover/copy:opacity-100">
            <CopyValue :value="formatJson(currentItem.headers)" hide-value title="Copy headers" />
          </div>
          <pre
            class="max-h-48 overflow-auto rounded-lg border border-gray-200 bg-gray-50 p-3 font-mono text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
          >{{ formatJson(currentItem.headers) }}</pre>
        </div>
      </div>

      <!-- Body -->
      <div>
        <h4 class="mb-2 text-sm font-medium text-gray-900 dark:text-white">Body</h4>
        <div class="group/copy relative">
          <div class="absolute right-2 top-2 opacity-0 transition-opacity group-hover/copy:opacity-100">
            <CopyValue :value="formatJson(currentItem.body)" hide-value title="Copy body" />
          </div>
          <pre
            class="max-h-64 overflow-auto rounded-lg border border-gray-200 bg-gray-50 p-3 font-mono text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
          >{{ formatJson(currentItem.body) }}</pre>
        </div>
      </div>
    </div>

    <template #footer-actions>
      <div v-if="currentItem && !loading" class="flex items-center justify-end gap-3">
        <Button
          variant="outline"
          :disabled="actionLoading"
          @click="handleApprove"
        >
          Approve
        </Button>
        <Button
          variant="outline"
          :disabled="actionLoading"
          @click="handleEdit"
        >
          Edit
        </Button>
        <Button
          variant="danger"
          :disabled="actionLoading"
          @click="handleReject"
        >
          Reject
        </Button>
      </div>
    </template>
  </Modal>

  <!-- Edit Message Modal -->
  <EditMessageModal
    v-if="currentItem"
    v-model="editModalOpen"
    :item="currentItem"
    @save="handleSave"
    @save-and-approve="handleSaveAndApprove"
  />

  <!-- Confirm Reject -->
  <Confirm
    v-model="confirmRejectOpen"
    id="confirm-reject-failed-modal"
    confirm-text="Yes, reject"
    cancel-text="Cancel"
    @confirm="handleConfirmReject"
  >
    <svg
      class="mx-auto mb-4 h-12 w-12 text-gray-400 dark:text-gray-200"
      aria-hidden="true"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 20 20"
    >
      <path
        stroke="currentColor"
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
      />
    </svg>
    <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
      Are you sure you want to reject this message?
    </h3>
  </Confirm>

  <!-- Confirm Reject All -->
  <Confirm
    v-model="confirmRejectAllOpen"
    id="confirm-reject-all-failed-modal"
    confirm-text="Yes, reject all"
    cancel-text="Cancel"
    @confirm="handleConfirmRejectAll"
  >
    <svg
      class="mx-auto mb-4 h-12 w-12 text-gray-400 dark:text-gray-200"
      aria-hidden="true"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 20 20"
    >
      <path
        stroke="currentColor"
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
      />
    </svg>
    <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
      Are you sure you want to reject all {{ totalCount }} messages?
    </h3>
  </Confirm>
</template>
