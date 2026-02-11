import api from '@/services/api'
import type { TopologyMetrics } from '@/types/topology-metrics'
import { useTopologyNodeMappings } from '@/composables/useTopologyNodeMappings'

interface MetricsApiItem {
  nodeId: string
  topologyId: string
  duration: number
  id: string
}

interface MetricsApiResponse {
  filter: unknown[]
  items: MetricsApiItem[]
  paging: {
    itemsPerPage: number
    lastPage: number
    nextPage: number
    page: number
    previousPage: number
    total: number
  }
  search: string | null
  sorter: Array<{ column: string; direction: string }>
}

function buildFilterObject(topologyId: string) {
  return {
    search: null,
    filter: [
      [
        {
          column: 'topologyId',
          operator: 'EQ',
          value: [topologyId],
        },
      ],
    ],
    sorter: [
      {
        column: 'duration',
        direction: 'DESC',
      },
    ],
    paging: {
      itemsPerPage: 9999,
      page: 1,
    },
  }
}

export const fetchTopologyMetrics = async (topologyId: string): Promise<TopologyMetrics> => {
  const { getNodeName } = useTopologyNodeMappings()

  const filterObj = buildFilterObject(topologyId)
  const filterParam = JSON.stringify(filterObj)

  const [processesResponse, requestsResponse] = await Promise.all([
    api.get<MetricsApiResponse>('/api/metrics/processes', {
      params: { filter: filterParam },
    }),
    api.get<MetricsApiResponse>('/api/metrics/requests', {
      params: { filter: filterParam },
    }),
  ])

  const nodeProcessTimes = processesResponse.data.items.map((item) => ({
    nodeName: getNodeName(item.nodeId),
    time: item.duration,
  }))

  const connectorRequestTimes = requestsResponse.data.items.map((item) => ({
    connectorName: getNodeName(item.nodeId),
    time: item.duration,
  }))

  return {
    nodeProcessTimes,
    connectorRequestTimes,
  }
}
