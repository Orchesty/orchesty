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

const EXCLUDED_NODE_TYPES = new Set(['start', 'cron', 'webhook', 'user'])

export const fetchTopologyMetrics = async (
  topologyId: string,
  mode: MetricsMode = 'last-run',
): Promise<TopologyMetrics> => {
  const { ensureLoaded, getNodeName } = useTopologyNodeMappings()
  await ensureLoaded()

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

  let excludedNodeIds = new Set<string>()
  try {
    const nodesResponse = await api.get<{ items: Array<{ _id: string; type: string }> }>(
      `/api/topologies/${topologyId}/nodes`,
    )
    const allNodes = nodesResponse.data.items || []
    excludedNodeIds = new Set(
      allNodes
        .filter((n) => EXCLUDED_NODE_TYPES.has(n.type))
        .map((n) => n._id),
    )
  } catch {
    // If nodes fetch fails, proceed without filtering
  }

  const nodeProcessTimes = processesResponse.data.items
    .filter((item) => !excludedNodeIds.has(item.nodeId))
    .map((item) => ({
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
