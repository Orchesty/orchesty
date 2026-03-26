<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import Textarea from '@/components/ui/datagrid/Textarea.vue'
import type { TrashItem } from '@/types/trash'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'

const { getTopologyName, getNodeName } = useTopologyNodeMappings()

interface Props {
  modelValue: boolean
  item: TrashItem
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  save: [data: { headers: Record<string, unknown>; body: Record<string, unknown> }]
  'save-and-approve': [data: { headers: Record<string, unknown>; body: Record<string, unknown> }]
}>()

const bodyJson = ref('')
const bodyError = ref('')

const topologyName = computed(() => getTopologyName(props.item.topologyId))
const nodeName = computed(() => getNodeName(props.item.nodeId))

watch(
  () => props.item,
  (newItem) => {
    if (newItem) {
      bodyJson.value = JSON.stringify(newItem.body, null, 2)
      bodyError.value = ''
    }
  },
  { immediate: true }
)

const validateJson = (jsonString: string): { valid: boolean; parsed?: unknown; error?: string } => {
  try {
    const parsed = JSON.parse(jsonString)
    return { valid: true, parsed }
  } catch (error) {
    return { valid: false, error: error instanceof Error ? error.message : 'Invalid JSON' }
  }
}

const isFormValid = computed(() => {
  return validateJson(bodyJson.value).valid
})

const getPayload = () => {
  const bodyValidation = validateJson(bodyJson.value)
  if (!bodyValidation.valid) {
    bodyError.value = bodyValidation.error || 'Invalid JSON'
    return null
  }
  return {
    headers: props.item.headers as Record<string, unknown>,
    body: bodyValidation.parsed as Record<string, unknown>,
  }
}

const handleSave = () => {
  const data = getPayload()
  if (data) emit('save', data)
}

const handleSaveAndApprove = () => {
  const data = getPayload()
  if (data) emit('save-and-approve', data)
}

watch(bodyJson, (text) => {
  const result = validateJson(text)
  bodyError.value = result.valid ? '' : (result.error || 'Invalid JSON')
})
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="edit-message-modal"
    title="Update message"
    size="xl"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <div class="space-y-4">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Topology</label>
          <p class="text-sm text-gray-900 dark:text-white">{{ topologyName }}</p>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Node</label>
          <p class="text-sm text-gray-900 dark:text-white">{{ nodeName }}</p>
        </div>
      </div>

      <div>
        <label
          for="body-textarea"
          class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
        >
          Body
        </label>
        <Textarea
          id="body-textarea"
          v-model="bodyJson"
          :rows="12"
          placeholder='{"key": "value"}'
          :error="bodyError"
        />
      </div>
    </div>

    <!-- Modal Footer -->
    <template #footer-actions>
      <div class="flex items-center justify-end gap-3">
        <Button variant="outline" @click="$emit('update:modelValue', false)">
          Close
        </Button>
        <Button variant="primary" :disabled="!isFormValid" @click="handleSave">
          Save
        </Button>
        <Button variant="primary" :disabled="!isFormValid" @click="handleSaveAndApprove">
          Save and Approve
        </Button>
      </div>
    </template>
  </Modal>
</template>

