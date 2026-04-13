import { ref } from 'vue'

const isOpen = ref(false)
const topologyId = ref('')
const nodeId = ref('')
const correlationId = ref('')
const nodeName = ref('')
const hideBulkActions = ref(false)

export function useFailedMessageModal() {
  const openFailedMessage = (params: {
    topologyId: string
    nodeId: string
    correlationId: string
    nodeName?: string
    hideBulkActions?: boolean
  }) => {
    topologyId.value = params.topologyId
    nodeId.value = params.nodeId
    correlationId.value = params.correlationId
    nodeName.value = params.nodeName ?? ''
    hideBulkActions.value = params.hideBulkActions ?? false
    isOpen.value = true
  }

  return {
    failedMessageOpen: isOpen,
    failedMessageTopologyId: topologyId,
    failedMessageNodeId: nodeId,
    failedMessageCorrelationId: correlationId,
    failedMessageNodeName: nodeName,
    failedMessageHideBulkActions: hideBulkActions,
    openFailedMessage,
  }
}
