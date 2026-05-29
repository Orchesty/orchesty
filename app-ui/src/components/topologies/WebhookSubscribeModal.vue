<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import { useToast } from '@/composables/useToast'
import { subscribeWebhookConfig } from '@/services/webhookConfigService'

interface Props {
  modelValue: boolean
  topologyName: string
  nodeName: string
  application: string
  /** Pre-filled JSON parameters; useful when re-opening for a re-subscribe. */
  initialParameters?: Record<string, unknown> | null
}

const props = withDefaults(defineProps<Props>(), {
  initialParameters: null,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  subscribed: []
}>()

const { showToast } = useToast()

const submitting = ref(false)
const parametersText = ref('')
const error = ref<string | null>(null)

// Derive the event slug from the canonical `${application}.${event}` node
// name. The user named the node by picking an event in the editor's webhook
// picker, so this is purely a display detail to confirm what they are
// subscribing to.
const eventName = computed(() => {
  if (props.application && props.nodeName.startsWith(`${props.application}.`)) {
    return props.nodeName.slice(props.application.length + 1)
  }
  const dot = props.nodeName.indexOf('.')
  return dot > 0 ? props.nodeName.slice(dot + 1) : props.nodeName
})

const parametersValid = computed(() => {
  if (!parametersText.value.trim()) return true
  try {
    const parsed = JSON.parse(parametersText.value)
    return parsed && typeof parsed === 'object' && !Array.isArray(parsed)
  } catch {
    return false
  }
})

watch(
  () => props.modelValue,
  (open) => {
    if (!open) return
    parametersText.value = props.initialParameters && Object.keys(props.initialParameters).length > 0
      ? JSON.stringify(props.initialParameters, null, 2)
      : ''
    error.value = null
    submitting.value = false
  },
)

const close = () => emit('update:modelValue', false)

const parseParameters = (): Record<string, unknown> | undefined => {
  if (!parametersText.value.trim()) return undefined
  try {
    return JSON.parse(parametersText.value) as Record<string, unknown>
  } catch {
    return undefined
  }
}

const handleSubscribe = async () => {
  if (!parametersValid.value) return
  submitting.value = true
  error.value = null
  try {
    await subscribeWebhookConfig(props.topologyName, props.nodeName, parseParameters())
    showToast('Webhook subscribed', 'success')
    emit('subscribed')
    close()
  } catch (e) {
    console.error('Webhook subscribe failed:', e)
    error.value = e instanceof Error ? e.message : 'Subscribe failed'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="webhook-subscribe-modal"
    title="Subscribe webhook"
    size="md"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <form id="webhook-subscribe-form" class="space-y-4" @submit.prevent="handleSubscribe">
      <div>
        <label class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
          {{ application }} / {{ eventName }}
        </label>
        <p class="text-[11px] text-gray-500 dark:text-gray-400">
          Confirm the subscription. Optionally provide application-specific
          parameters that will be forwarded to the upstream API on subscribe
          (e.g. a filter, channel, source). To change parameters later,
          unsubscribe and subscribe again.
        </p>
      </div>

      <div>
        <label
          for="webhook-subscribe-parameters"
          class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
        >
          Parameters (JSON object, optional)
        </label>
        <textarea
          id="webhook-subscribe-parameters"
          v-model="parametersText"
          rows="4"
          class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 font-mono text-xs text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-primary-500 dark:focus:ring-primary-500"
          :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500': !parametersValid }"
          placeholder='{ "filter": "orders.created" }'
        ></textarea>
        <p v-if="!parametersValid" class="mt-2 text-xs text-red-600 dark:text-red-400">
          Parameters must be a valid JSON object.
        </p>
      </div>

      <div
        v-if="error"
        class="rounded-lg border border-red-300 bg-red-50 p-2.5 text-xs text-red-700 dark:border-red-600 dark:bg-red-900/30 dark:text-red-300"
      >
        {{ error }}
      </div>
    </form>

    <template #footer-actions>
      <Button variant="outline" @click="close">Cancel</Button>
      <Button
        variant="primary"
        type="submit"
        form="webhook-subscribe-form"
        :disabled="!parametersValid || submitting"
      >
        {{ submitting ? 'Subscribing…' : 'Subscribe' }}
      </Button>
    </template>
  </Modal>
</template>
