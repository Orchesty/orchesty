<script setup lang="ts">
import { ref, computed } from 'vue'
import Drawer from '@/components/ui/Drawer.vue'
import Button from '@/components/ui/Button.vue'
import CopyValue from '@/components/ui/CopyValue.vue'
import Confirm from '@/components/ui/Confirm.vue'
import EditMessageModal from './EditMessageModal.vue'
import type { TrashItem } from '@/types/trash'
import { useDateFormat } from '@/composables/useDateFormat'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'
import { formatJson } from '@/utils/formatters'

const { formatDateTime } = useDateFormat()
const { getTopologyName, getNodeName } = useTopologyNodeMappings()

interface Props {
  modelValue: boolean
  item: TrashItem | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  approve: []
  update: [data: { headers: Record<string, unknown>; body: Record<string, unknown> }]
  reject: []
}>()

// Edit modal state
const editModalOpen = ref(false)

// Confirm modal state
const confirmModalOpen = ref(false)

// Format timestamp for display
const formattedTimestamp = computed(() => {
  if (!props.item) return ''
  return formatDateTime(props.item.timestamp)
})

const handleApprove = () => {
  emit('approve')
}

const handleEdit = () => {
  editModalOpen.value = true
}

const handleSave = (data: { headers: Record<string, unknown>; body: Record<string, unknown> }) => {
  emit('update', data)
  editModalOpen.value = false
}

const handleSaveAndApprove = (data: { headers: Record<string, unknown>; body: Record<string, unknown> }) => {
  emit('update', data)
  emit('approve')
  editModalOpen.value = false
}

const handleReject = () => {
  confirmModalOpen.value = true
}

const handleConfirmReject = () => {
  emit('reject')
  confirmModalOpen.value = false
}
</script>

<template>
  <Drawer
    :model-value="modelValue"
    id="trash-detail-drawer"
    label="Message Detail"
    width="w-1/2 min-w-[600px]"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div v-if="item" class="space-y-6">
      <!-- Attributes Section -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
            Topology
          </label>
          <p class="text-sm text-gray-900 dark:text-white">{{ getTopologyName(item.topologyId) }}</p>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
            Topology ID
          </label>
          <p class="font-mono text-sm text-gray-900 dark:text-white">{{ item.topologyId }}</p>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
            Node
          </label>
          <p class="text-sm text-gray-900 dark:text-white">{{ getNodeName(item.nodeId) }}</p>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
            Node ID
          </label>
          <p class="font-mono text-sm text-gray-900 dark:text-white">{{ item.nodeId }}</p>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
            Correlation ID
          </label>
          <CopyValue :value="item.correlationId">
            <span class="font-mono text-sm text-gray-900 dark:text-white">{{ item.correlationId }}</span>
          </CopyValue>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">
            Timestamp
          </label>
          <p class="text-sm text-gray-900 dark:text-white">{{ formattedTimestamp }}</p>
        </div>
      </div>

      <!-- Headers Card -->
      <div>
        <h4 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">Headers</h4>
        <pre
          class="overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-4 font-mono text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
        >{{ formatJson(item.headers) }}</pre>
      </div>

      <!-- Body Card -->
      <div>
        <h4 class="mb-3 text-sm font-medium text-gray-900 dark:text-white">Body</h4>
        <pre
          class="overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-4 font-mono text-sm text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
        >{{ formatJson(item.body) }}</pre>
      </div>
    </div>

    <!-- Footer Actions -->
    <template #footer-actions>
      <div class="flex items-center justify-end gap-3">
        <Button variant="outline" @click="handleApprove">
          Approve
        </Button>
        <Button variant="outline" @click="handleEdit">
          Edit
        </Button>
        <Button variant="danger" @click="handleReject">
          Reject
        </Button>
      </div>
    </template>
  </Drawer>

  <!-- Edit Message Modal -->
  <EditMessageModal
    v-if="item"
    v-model="editModalOpen"
    :item="item"
    @save="handleSave"
    @save-and-approve="handleSaveAndApprove"
  />

  <!-- Confirm Reject Modal -->
  <Confirm
    v-model="confirmModalOpen"
    id="confirm-reject-modal"
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
</template>

