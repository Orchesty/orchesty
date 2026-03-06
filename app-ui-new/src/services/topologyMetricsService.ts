import api from '@/services/api'
import type { TopologyMetrics, MetricsMode } from '@/types/topology-metrics'
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

const PROCESSES_ENDPOINT = '/api/metrics/processes'
const REQUESTS_ENDPOINT = '/api/metrics/requests'

export interface RawNodeMetrics {
  processTimeByNodeId: Record<string, number>
  requestTimeByNodeId: Record<string, number>
}

export const fetchRawTopologyMetrics = async (
  topologyId: string,
): Promise<RawNodeMetrics> => {
  const filterObj = buildFilterObject(topologyId)
  const filterParam = JSON.stringify(filterObj)

  const [processesResponse, requestsResponse] = await Promise.all([
    api.get<MetricsApiResponse>(PROCESSES_ENDPOINT, {
      params: { filter: filterParam, lastRun: 1 },
    }),
    api.get<MetricsApiResponse>(REQUESTS_ENDPOINT, {
      params: { filter: filterParam, lastRun: 1 },
    }),
  ])

  const processTimeByNodeId: Record<string, number> = {}
  for (const item of processesResponse.data.items) {
    processTimeByNodeId[item.nodeId] = item.duration
  }

  const requestTimeByNodeId: Record<string, number> = {}
  for (const item of requestsResponse.data.items) {
    requestTimeByNodeId[item.nodeId] = item.duration
  }

  return { processTimeByNodeId, requestTimeByNodeId }
}

export const fetchTopologyMetrics = async (
  topologyId: string,
  mode: MetricsMode = 'last-run',
): Promise<TopologyMetrics> => {
  const { getNodeName } = useTopologyNodeMappings()

  const filterObj = buildFilterObject(topologyId)
  const filterParam = JSON.stringify(filterObj)
  const lastRun = mode === 'last-run' ? 1 : undefined

  const [processesResponse, requestsResponse] = await Promise.all([
    api.get<MetricsApiResponse>(PROCESSES_ENDPOINT, {
      params: { filter: filterParam, lastRun },
    }),
    api.get<MetricsApiResponse>(REQUESTS_ENDPOINT, {
      params: { filter: filterParam, lastRun },
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
