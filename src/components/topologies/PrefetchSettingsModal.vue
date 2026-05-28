<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import { useToast } from '@/composables/useToast'
import { updateNodePrefetch } from '@/services/nodeService'
import { republishTopology } from '@/services/topologiesService'

interface Props {
  modelValue: boolean
  nodeId: string
  nodeName: string
  topologyId: string
  currentPrefetch?: number
}

const props = withDefaults(defineProps<Props>(), {
  currentPrefetch: 1,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  /** Saved (and optionally republished). */
  saved: [republished: boolean]
}>()

const { showToast } = useToast()

const prefetchValue = ref<number>(1)
const submitting = ref(false)

const PREFETCH_MIN = 1
const PREFETCH_MAX = 20

const isValid = computed(
  () =>
    Number.isInteger(prefetchValue.value) &&
    prefetchValue.value >= PREFETCH_MIN &&
    prefetchValue.value <= PREFETCH_MAX,
)
const showOrderWarning = computed(() => isValid.value && prefetchValue.value > 1)
const dirty = computed(() => prefetchValue.value !== props.currentPrefetch)

watch(
  () => props.modelValue,
  (open) => {
    if (!open) return
    const initial = Math.floor(props.currentPrefetch ?? PREFETCH_MIN)
    prefetchValue.value = Math.min(PREFETCH_MAX, Math.max(PREFETCH_MIN, initial))
    submitting.value = false
  },
)

const close = () => emit('update:modelValue', false)

const persistPrefetch = async (): Promise<boolean> => {
  if (!isValid.value) return false
  if (!dirty.value) return true
  try {
    await updateNodePrefetch(props.nodeId, prefetchValue.value)
    return true
  } catch (e) {
    console.error('Failed to update prefetch:', e)
    showToast(`Failed to update prefetch: ${(e as Error).message}`, 'error')
    return false
  }
}

const handleSaveAndContinue = async () => {
  submitting.value = true
  try {
    const ok = await persistPrefetch()
    if (!ok) return
    showToast('Prefetch saved. Republish the topology to apply on the bridge.', 'success')
    emit('saved', false)
    close()
  } finally {
    submitting.value = false
  }
}

const handleSaveAndRepublish = async () => {
  submitting.value = true
  try {
    const ok = await persistPrefetch()
    if (!ok) return
    try {
      await republishTopology(props.topologyId)
      showToast('Prefetch saved and bridge republished', 'success')
      emit('saved', true)
      close()
    } catch (e) {
      console.error('Republish failed:', e)
      showToast(
        `Saved, but republish failed: ${(e as Error).message}. Use the banner to retry.`,
        'error',
      )
      emit('saved', false)
      close()
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="prefetch-settings-modal"
    title="Prefetch settings"
    size="xl"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <form id="prefetch-settings-form" class="space-y-4" @submit.prevent="handleSaveAndRepublish">
      <div>
        <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          {{ nodeName }}
        </label>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          Prefetch controls how many messages this node consumes from RabbitMQ
          in parallel.
          <strong class="font-semibold text-gray-700 dark:text-gray-200">
            A value greater than 1 means the topology no longer guarantees
            message ordering for this node
          </strong>
          — concurrent workers may finish out of order. Keep it at
          <code>1</code> whenever order matters. Changes only take effect after
          the bridge is republished.
        </p>
      </div>

      <div>
        <label
          for="prefetch-input"
          class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
        >
          Prefetch (1–20)
        </label>
        <input
          id="prefetch-input"
          v-model.number="prefetchValue"
          type="number"
          :min="PREFETCH_MIN"
          :max="PREFETCH_MAX"
          step="1"
          class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-primary-500 dark:focus:ring-primary-500"
          :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': !isValid }"
        />
        <p v-if="!isValid" class="mt-2 text-xs text-red-600 dark:text-red-400">
          Prefetch must be an integer between {{ PREFETCH_MIN }} and {{ PREFETCH_MAX }}.
        </p>
        <p
          v-else-if="showOrderWarning"
          class="mt-2 text-xs text-amber-600 dark:text-amber-400"
        >
          ⚠ Message order is not guaranteed when prefetch &gt; 1.
        </p>
      </div>
    </form>

    <template #footer-actions>
      <Button variant="outline" @click="close">Cancel</Button>
      <Button
        variant="outline"
        type="button"
        :disabled="!isValid || submitting"
        @click="handleSaveAndContinue"
      >
        Save and continue editing
      </Button>
      <Button
        variant="primary"
        type="submit"
        form="prefetch-settings-form"
        :disabled="!isValid || submitting"
      >
        {{ submitting ? 'Saving…' : 'Save & Republish' }}
      </Button>
    </template>
  </Modal>
</template>
