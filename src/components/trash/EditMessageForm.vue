<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Textarea from '@/components/ui/datagrid/Textarea.vue'
import type { TrashItem } from '@/types/trash'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'

interface Props {
  item: TrashItem
}

const props = defineProps<Props>()

const { getTopologyName, getNodeName } = useTopologyNodeMappings()

const bodyJson = ref('')
const bodyError = ref('')

const topologyName = computed(() => getTopologyName(props.item.topologyId))
const nodeName = computed(() => getNodeName(props.item.nodeId))

const validateJson = (jsonString: string): { valid: true; parsed: unknown } | { valid: false; error: string } => {
  try {
    return { valid: true, parsed: JSON.parse(jsonString) }
  } catch (error) {
    return { valid: false, error: error instanceof Error ? error.message : 'Invalid JSON' }
  }
}

watch(
  () => props.item,
  (newItem) => {
    if (newItem) {
      bodyJson.value = JSON.stringify(newItem.body, null, 2)
      bodyError.value = ''
    }
  },
  { immediate: true },
)

watch(bodyJson, (text) => {
  const result = validateJson(text)
  bodyError.value = result.valid ? '' : result.error
})

const isValid = computed(() => validateJson(bodyJson.value).valid)

function getPayload(): { headers: Record<string, unknown>; body: Record<string, unknown> } | null {
  const result = validateJson(bodyJson.value)
  if (!result.valid) {
    bodyError.value = result.error
    return null
  }
  return {
    headers: props.item.headers as Record<string, unknown>,
    body: result.parsed as Record<string, unknown>,
  }
}

defineExpose({ isValid, getPayload })
</script>

<template>
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
</template>
