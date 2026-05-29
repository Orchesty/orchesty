<script setup lang="ts">
import { RouterView } from 'vue-router'
import AppNavbar from '@/components/layout/AppNavbar.vue'
import AppSidebar from '@/components/layout/AppSidebar.vue'
import ConnectorMetricDetailModal from '@/components/dashboard/ConnectorMetricDetailModal.vue'
import FailedMessageModal from '@/components/topologies/FailedMessageModal.vue'
import { provideHelp } from '@/composables/useHelp'
import { useConnectorMetricDetail } from '@/composables/useConnectorMetricDetail'
import { useFailedMessageModal } from '@/composables/useFailedMessageModal'

provideHelp()
const { metricDetailOpen, selectedRecord } = useConnectorMetricDetail()
const {
  failedMessageOpen,
  failedMessageTopologyId,
  failedMessageNodeId,
  failedMessageCorrelationId,
  failedMessageNodeName,
  failedMessageHideBulkActions,
} = useFailedMessageModal()
</script>

<template>
  <div class="flex h-screen flex-col overflow-hidden bg-gray-50 dark:bg-gray-900">
    <AppNavbar />
    <div class="flex flex-1 overflow-hidden">
      <AppSidebar />
      <div id="main-content" class="flex-1 overflow-hidden bg-gray-50 dark:bg-gray-900">
        <RouterView />
      </div>
    </div>
    <ConnectorMetricDetailModal v-model="metricDetailOpen" :record="selectedRecord" />
    <FailedMessageModal
      v-model="failedMessageOpen"
      :topology-id="failedMessageTopologyId"
      :node-id="failedMessageNodeId"
      :correlation-id="failedMessageCorrelationId"
      :node-name="failedMessageNodeName"
      :hide-bulk-actions="failedMessageHideBulkActions"
      modal-id="failed-message-modal-global"
    />
  </div>
</template>
