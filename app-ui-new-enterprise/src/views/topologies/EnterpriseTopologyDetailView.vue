<script setup lang="ts">
import { ref, computed } from 'vue'
import { TopologyDetailView, useAuthorization } from '@orchesty/ui-core'
import type { MoreActionsSection } from '@orchesty/ui-core'
import Confirm from '@/components/ui/Confirm.vue'
import { useFeatures } from '@/composables/useFeatures'
import { useToast } from '@/composables/useToast'
import { decommissionBridge, restartBridge } from '@/services/resourcesService'
import TopologyAccessDrawer from '@/components/topologies/TopologyAccessDrawer.vue'
import { AlertTriangle } from 'lucide-vue-next'

interface Props {
  id: string
}

const props = defineProps<Props>()

const { pulse } = useFeatures()
const { hasRole } = useAuthorization()
const { showToast } = useToast()

const hiddenTabs = computed(() => {
  const tabs: string[] = []
  if (!pulse.value) tabs.push('context')
  return tabs
})

const accessDrawerOpen = ref(false)
const unpublishConfirmOpen = ref(false)
const unpublishing = ref(false)
const restarting = ref(false)

async function handleRestartBridge() {
  restarting.value = true
  try {
    await restartBridge(props.id)
    showToast('Bridge has been restarted', 'success')
  } catch (err) {
    console.error('Failed to restart bridge:', err)
    showToast('Failed to restart bridge', 'error')
  } finally {
    restarting.value = false
  }
}

async function handleConfirmUnpublish() {
  unpublishing.value = true
  try {
    await decommissionBridge(props.id, true)
    showToast('Topology has been unpublished and bridge decommissioned', 'success')
    unpublishConfirmOpen.value = false
    window.location.reload()
  } catch (err) {
    console.error('Failed to unpublish topology:', err)
    showToast('Failed to unpublish topology', 'error')
  } finally {
    unpublishing.value = false
  }
}

const extraMoreActions = computed<MoreActionsSection[]>(() => {
  if (!hasRole('system_manager')) return []
  return [
    {
      items: [
        { type: 'button', label: 'Access', onClick: () => { accessDrawerOpen.value = true } },
        { type: 'button', label: restarting.value ? 'Restarting...' : 'Restart topology', onClick: restarting.value ? () => {} : handleRestartBridge },
        { type: 'button', label: 'Unpublish', onClick: () => { unpublishConfirmOpen.value = true } },
      ],
    },
  ]
})
</script>

<template>
  <TopologyDetailView
    :id="id"
    :hidden-tabs="hiddenTabs"
    :extra-more-actions="extraMoreActions"
  >
    <template #extra-drawers="{ topology }">
      <TopologyAccessDrawer
        v-model="accessDrawerOpen"
        :topology-id="topology._id"
        :topology-name="topology.name"
      />

      <Confirm
        v-model="unpublishConfirmOpen"
        id="unpublish-topology-confirm"
        :confirm-text="unpublishing ? 'Unpublishing...' : 'Unpublish'"
        confirm-variant="danger"
        size="lg"
        @confirm="handleConfirmUnpublish"
      >
        <AlertTriangle class="mx-auto mb-3 h-12 w-12 text-amber-500" :stroke-width="1.5" />
        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Unpublish Topology</h3>
        <p class="text-gray-500 dark:text-gray-400">
          Topology <strong>{{ topology.name }} v{{ topology.version }}</strong> will be unpublished. The bridge container will be stopped and removed, including RabbitMQ queues.
        </p>
      </Confirm>
    </template>
  </TopologyDetailView>
</template>
