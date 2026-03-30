import type {
  TrashItem,
  TrashApiFilter,
  TrashApiResponse,
  TrashItemApi,
} from '@/types/trash'
import api from './api'

function mapApiItemToBreakpointItem(apiItem: TrashItemApi): TrashItem {
  let parsedBody: Record<string, unknown> = {}
  try {
    parsedBody = JSON.parse(apiItem.body)
  } catch {
    // body might not be valid JSON
  }

  return {
    id: apiItem.id,
    nodeId: apiItem.nodeId,
    topologyId: apiItem.topologyId,
    correlationId: apiItem.correlationId,
    timestamp: apiItem.created,
    topology: apiItem.topologyId,
    node: apiItem.nodeId,
    headers: apiItem.headers || {},
    body: parsedBody,
  }
}

export async function fetchBreakpointItems(params: {
  topologyId?: string
  nodeId?: string
  page?: number
  perPage?: number
}): Promise<{ data: TrashItem[]; total: number }> {
  const filterObj: TrashApiFilter = {
    search: null,
    filter: [
      [{ column: 'type', operator: 'EQ', value: ['userTask'] }],
    ],
    sorter: [{ column: 'created', direction: 'ASC' }],
    paging: {
      itemsPerPage: params.perPage || 1,
      page: params.page || 1,
    },
  }

  if (params.topologyId) {
    filterObj.filter.push([
      { column: 'topologyId', operator: 'EQ', value: [params.topologyId] },
    ])
  }

  if (params.nodeId) {
    filterObj.filter.push([
      { column: 'nodeId', operator: 'EQ', value: [params.nodeId] },
    ])
  }

  const encodedFilter = encodeURIComponent(JSON.stringify(filterObj))
  const response = await api.get<TrashApiResponse>(
    `/api/user-tasks?filter=${encodedFilter}`,
  )

  return {
    data: response.data.items.map(mapApiItemToBreakpointItem),
    total: response.data.paging.total,
  }
}

export interface NodeOverlayCounts {
  breakpointCounts: Record<string, number | string>
  failedNodeIds: string[]
  breakpointCorrelationId?: string
}

function buildBreakpointCounts(
  data: TrashApiResponse,
): Record<string, number | string> {
  const perNode: Record<string, number> = {}
  for (const item of data.items) {
    perNode[item.nodeId] = (perNode[item.nodeId] || 0) + 1
  }
  const total = data.paging.total
  const fetched = data.items.length
  if (total <= fetched) return perNode

  const nodeIds = Object.keys(perNode)
  if (nodeIds.length === 1) {
    const onlyId = nodeIds[0]!
    return { [onlyId]: total }
  }
  const result: Record<string, number | string> = {}
  for (const [nodeId, count] of Object.entries(perNode)) {
    result[nodeId] = `${count}+`
  }
  return result
}

/**
 * Fetches breakpoint counts and failed node IDs via two parallel requests
 * (the grid API does not return the `type` field, so a single combined
 * request cannot be split on the frontend).
 */
export async function fetchNodeOverlayCounts(
  topologyId: string,
  correlationId?: string,
): Promise<NodeOverlayCounts> {
  const breakpointFilter: TrashApiFilter = {
    search: null,
    filter: [
      [{ column: 'type', operator: 'EQ', value: ['userTask'] }],
      [{ column: 'topologyId', operator: 'EQ', value: [topologyId] }],
    ],
    sorter: [{ column: 'created', direction: 'ASC' }],
    paging: { itemsPerPage: 1000, page: 1 },
  }

  const trashFilter: TrashApiFilter = {
    search: null,
    filter: [
      [{ column: 'type', operator: 'EQ', value: ['trash'] }],
      [{ column: 'topologyId', operator: 'EQ', value: [topologyId] }],
    ],
    sorter: [{ column: 'created', direction: 'ASC' }],
    paging: { itemsPerPage: 1000, page: 1 },
  }

  if (!correlationId) {
    const breakpointRes = await api.get<TrashApiResponse>(
      `/api/user-tasks?filter=${encodeURIComponent(JSON.stringify(breakpointFilter))}`,
    )
    const breakpointCounts = buildBreakpointCounts(breakpointRes.data)
    const firstItem = breakpointRes.data.items[0]
    return {
      breakpointCounts,
      failedNodeIds: [],
      breakpointCorrelationId: firstItem?.correlationId,
    }
  }

  trashFilter.filter.push([
    { column: 'correlationId', operator: 'EQ', value: [correlationId] },
  ])

  const [breakpointRes, trashRes] = await Promise.all([
    api.get<TrashApiResponse>(
      `/api/user-tasks?filter=${encodeURIComponent(JSON.stringify(breakpointFilter))}`,
    ),
    api.get<TrashApiResponse>(
      `/api/user-tasks?filter=${encodeURIComponent(JSON.stringify(trashFilter))}`,
    ),
  ])

  const breakpointCounts = buildBreakpointCounts(breakpointRes.data)

  const failedNodeIdSet = new Set<string>()
  for (const item of trashRes.data.items) {
    failedNodeIdSet.add(item.nodeId)
  }

  const firstBreakpoint = breakpointRes.data.items[0]
  return {
    breakpointCounts,
    failedNodeIds: [...failedNodeIdSet],
    breakpointCorrelationId: firstBreakpoint?.correlationId,
  }
}

export async function approveBreakpointItem(id: string): Promise<void> {
  await api.post(`/api/user-task/${id}/accept`)
}

export async function rejectBreakpointItem(id: string): Promise<void> {
  await api.post(`/api/user-task/${id}/reject`)
}

export async function updateBreakpointItem(
  id: string,
  data: { headers: Record<string, unknown>; body: Record<string, unknown> },
): Promise<{ headers: Record<string, unknown>; body: Record<string, unknown> }> {
  const payload = {
    body: JSON.stringify(data.body),
    headers: data.headers,
  }
  const response = await api.put<{
    message: { body: string; headers: Record<string, unknown> }
  }>(`/api/user-task/${id}`, payload)

  let parsedBody: Record<string, unknown> = {}
  try {
    parsedBody = JSON.parse(response.data.message.body)
  } catch {
    // body might not be valid JSON
  }

  return {
    headers: response.data.message.headers,
    body: parsedBody,
  }
}

export async function approveAllBreakpoints(topologyId: string, nodeId?: string): Promise<void> {
  const payload: Record<string, string> = { topologyId, type: 'userTask' }
  if (nodeId) payload.nodeId = nodeId
  await api.post('/api/user-task/accept', payload)
}

export async function rejectAllBreakpoints(topologyId: string, nodeId?: string): Promise<void> {
  const payload: Record<string, string> = { topologyId, type: 'userTask' }
  if (nodeId) payload.nodeId = nodeId
  await api.post('/api/user-task/reject', payload)
}

export async function hasBreakpointMessages(topologyId: string): Promise<boolean> {
  const result = await fetchBreakpointItems({ topologyId, perPage: 1 })
  return result.total > 0
}
