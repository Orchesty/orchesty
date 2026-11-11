import { ref } from 'vue'
import type { ConnectorErrorRecord } from '@/types/connectors'

const metricDetailOpen = ref(false)
const selectedRecord = ref<ConnectorErrorRecord | null>(null)

export function useConnectorMetricDetail() {
  const openMetricDetail = (record: ConnectorErrorRecord) => {
    selectedRecord.value = record
    metricDetailOpen.value = true
  }

  return {
    metricDetailOpen,
    selectedRecord,
    openMetricDetail,
  }
}
