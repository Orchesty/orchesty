<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import { useToast } from '@/composables/useToast'
import {
  fetchBreakpointItems,
  approveBreakpointItem,
} from '@/services/breakpointService'
import type { TrashItem } from '@/types/trash'

interface Props {
  modelValue: boolean
  nodeName: string
  nodeId: string
  topologyId: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'update': []
}>()

const { showToast } = useToast()

const currentItem = ref<TrashItem | null>(null)
const totalCount = ref(0)
const loading = ref(false)
const actionLoading = ref(false)

const modalTitle = computed(() => `Breakpoint: ${props.nodeName}`)

const formatJson = (obj: Record<string, unknown>): string => {
  return JSON.stringify(obj, null, 2)
}

const loadFirstItem = async () => {
  loading.value = true
  try {
    const result = await fetchBreakpointItems({
      topologyId: props.topologyId,
      nodeId: props.nodeId,
      page: 1,
      perPage: 1,
    })
    totalCount.value = result.total
    if (result.data.length > 0) {
      currentItem.value = result.data[0]
    } else {
      currentItem.value = null
      handleClose()
    }
  } catch (err) {
    console.error('Failed to load breakpoint item:', err)
    showToast('Failed to load breakpoint message', 'error')
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
    await approveBreakpointItem(currentItem.value.id)
    showToast('Message approved', 'success')
    emit('update')

    const result = await fetchBreakpointItems({
      topologyId: props.topologyId,
      nodeId: props.nodeId,
      page: 1,
      perPage: 1,
    })
    if (result.data.length > 0) {
      totalCount.value = result.total
      currentItem.value = result.data[0]
    } else {
      handleClose()
    }
  } catch (err) {
    console.error('Failed to approve:', err)
    showToast('Failed to approve message', 'error')
  } finally {
    actionLoading.value = false
  }
}

const handleClose = () => {
  emit('update:modelValue', false)
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="breakpoint-modal"
    :title="modalTitle"
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
        <circle
          class="opacity-25"
          cx="12"
          cy="12"
          r="10"
          stroke="currentColor"
          stroke-width="4"
        />
        <path
          class="opacity-75"
          fill="currentColor"
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
        />
      </svg>
    </div>

    <!-- Message content -->
    <div v-else-if="currentItem" class="space-y-4">
      <!-- Counter -->
      <div
        class="inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300"
      >
        <span>{{ totalCount }} message{{ totalCount !== 1 ? 's' : '' }} in queue</span>
      </div>

      <!-- Headers -->
      <div>
        <h4
          class="mb-2 text-sm font-medium text-gray-900 dark:text-white"
        >
          Headers
        </h4>
        <pre
          class="max-h-48 overflow-auto rounded-lg border border-gray-200 bg-gray-50 p-3 font-mono text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
        >{{ formatJson(currentItem.headers) }}</pre>
      </div>

      <!-- Body -->
      <div>
        <h4
          class="mb-2 text-sm font-medium text-gray-900 dark:text-white"
        >
          Body
        </h4>
        <pre
          class="max-h-64 overflow-auto rounded-lg border border-gray-200 bg-gray-50 p-3 font-mono text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
        >{{ formatJson(currentItem.body) }}</pre>
      </div>
    </div>

    <template #footer-actions>
      <div v-if="currentItem && !loading" class="flex items-center justify-end gap-3">
        <Button
          variant="success"
          :disabled="actionLoading"
          @click="handleApprove"
        >
          Approve
        </Button>
      </div>
    </template>
  </Modal>
</template>
