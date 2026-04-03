<script setup lang="ts">
import { ref, computed } from 'vue'
import { TopologyDetailView, useAuthorization } from '@orchesty/ui-core'
import type { MoreActionsSection } from '@orchesty/ui-core'
import { useFeatures } from '@/composables/useFeatures'
import TopologyAccessDrawer from '@/components/topologies/TopologyAccessDrawer.vue'

interface Props {
  id: string
}

defineProps<Props>()

const { pulse } = useFeatures()
const { hasRole } = useAuthorization()

const hiddenTabs = computed(() => {
  const tabs: string[] = []
  if (!pulse.value) tabs.push('context')
  return tabs
})

const accessDrawerOpen = ref(false)

const extraMoreActions = computed<MoreActionsSection[]>(() => {
  if (!hasRole('system_manager')) return []
  return [
    {
      items: [
        { type: 'button', label: 'Access', onClick: () => { accessDrawerOpen.value = true } },
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
    </template>
  </TopologyDetailView>
</template>
