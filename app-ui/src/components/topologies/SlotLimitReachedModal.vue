<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { Layers } from 'lucide-vue-next'
import Modal from '@/components/ui/Modal.vue'
import Button from '@/components/ui/Button.vue'
import { useAuthorization } from '@/composables/useAuthorization'

interface Props {
  modelValue: boolean
  used?: number | null
  limit?: number | null
}

const props = withDefaults(defineProps<Props>(), {
  used: null,
  limit: null,
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

const router = useRouter()
const { hasRole } = useAuthorization()

// Resources page (free a slot by decommissioning unused versions) is a
// system-manager-only view in the cloud edition. Only surface the CTA to users
// who can actually open it; everyone else gets the explanation and is told to
// reach out to the system manager. Slot gate is disabled in OSS, so this modal
// only ever opens in cloud where the route exists.
const canOpenResources = computed(() => hasRole('system_manager'))

const headline = computed(() => {
  if (props.used !== null && props.limit !== null) {
    return `${props.used} of ${props.limit} topology slots are in use.`
  }
  return 'All topology slots in this instance are in use.'
})

const close = () => {
  emit('update:modelValue', false)
}

const openResources = () => {
  close()
  router.push({ name: 'resources' }).catch(() => {
    // Resources route only exists in the cloud edition. If somehow this fires
    // in an environment without it, fail silently — modal is already closed.
  })
}
</script>

<template>
  <Modal
    :model-value="modelValue"
    id="topology-slot-limit-modal"
    title="Topology slot limit reached"
    size="lg"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <div class="space-y-4">
      <div class="flex items-start gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
          <Layers class="h-5 w-5" :stroke-width="2" />
        </div>
        <div class="space-y-1">
          <p class="text-sm font-semibold text-gray-900 dark:text-white">
            {{ headline }}
          </p>
          <p class="text-sm text-gray-600 dark:text-gray-300">
            Each published topology version consumes one slot. Disabling a topology does
            <strong>not</strong> free its slot — the bridge container keeps running until the
            version is unpublished or deleted.
          </p>
        </div>
      </div>

      <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900/40">
        <p class="mb-3 text-sm font-medium text-gray-900 dark:text-white">
          To publish this topology, choose one of the following:
        </p>
        <ol class="list-decimal space-y-3 pl-5 text-sm text-gray-600 dark:text-gray-300 marker:text-gray-400 dark:marker:text-gray-500">
          <li>
            <span class="font-medium text-gray-900 dark:text-white">Free a slot.</span>
            Open the
            <span class="font-medium">Resources</span>
            page and decommission a topology version you no longer need. Older versions
            of the same topology are usually safe to remove once a newer version is
            already published.
          </li>
          <li>
            <span class="font-medium text-gray-900 dark:text-white">Upgrade your plan.</span>
            Larger plans include more topology slots. Contact your account owner or
            Orchesty support to move to a higher tier.
          </li>
        </ol>
      </div>

      <p
        v-if="!canOpenResources"
        class="rounded-md bg-amber-50 p-3 text-xs text-amber-800 dark:bg-amber-900/20 dark:text-amber-300"
      >
        You don't have permission to open the Resources page. Please ask the system
        manager of this instance to free a slot or upgrade the plan.
      </p>
    </div>

    <template #footer-actions>
      <Button variant="outline" @click="close">
        Close
      </Button>
      <Button
        v-if="canOpenResources"
        variant="primary"
        @click="openResources"
      >
        Open Resources
      </Button>
    </template>
  </Modal>
</template>
