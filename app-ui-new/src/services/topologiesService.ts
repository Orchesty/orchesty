import type { PaginatedResponse } from '@/types/api'
import type {
  Topology,
  TopologyQueryParams,
  TopologyApiItem,
  TopologyApiResponse,
  TopologyApiFilter
} from '@/types/topologies'
import type {
  TopologyDetail,
  TopologyVersion,
  TopologiesTreeNode,
  FolderItem,
  TopologyItem,
} from '@/types/topologies-page'
import api from '@/services/api'
import { useDateFormat } from '@/composables/useDateFormat'

const { formatDateTime } = useDateFormat()

// ------ Sidebar API types ------

interface CategoryApiItem {
  _id: string
  name: string
  parent: string | null
  system?: boolean
}

interface CategoriesApiResponse {
  items: CategoryApiItem[]
  total: number
  limit: number | null
  count: number
  offset: number
}

interface TopologyListApiItem {
  _id: string
  name: string
  category: string | null
  cronSettings: Array<{ cron: string; cronParams: string }>
  description: string
  enabled: boolean
  status: string
  type: string
  version: number
  visibility: string
}

interface TopologyListApiResponse {
  items: TopologyListApiItem[]
  total: number
  limit: number | null
  count: number
  offset: number | null
}

/**
 * Map API status to UI status
 */
function mapApiStatusToUiStatus(apiStatus: string): 'success' | 'running' | 'failed' {
  if (apiStatus === 'COMPLETED') return 'success'
  if (apiStatus === 'RUNNING') return 'running'
  if (apiStatus === 'FAILED') return 'failed'
  return 'success'
}

/**
 * Fetch all topologies merged with time-filtered metrics.
 * Returns ALL topologies regardless of activity; metrics show zeros where no activity exists.
 */
export async function fetchTopologies(
  params: TopologyQueryParams,
): Promise<PaginatedResponse<Topology>> {
  const metricsFilter: TopologyApiFilter = {
    search: null,
    filter: [],
    sorter: [],
    paging: { itemsPerPage: 9999, page: 1 },
  }

  if (params.dateFrom) {
    metricsFilter.filter.push([params.dateTo
      ? { column: 'created', operator: 'BETWEEN', value: [params.dateFrom, params.dateTo] }
      : { column: 'created', operator: 'GTE', value: [params.dateFrom] }
    ])
  }

  const [topologiesResponse, metricsResponse] = await Promise.all([
    api.get<TopologyListApiResponse>('/api/topologies'),
    api.get<TopologyApiResponse>('/api/processes/topologies', {
      params: { filter: JSON.stringify(metricsFilter) },
    }),
  ])

  const metricsMap = new Map<string, TopologyApiItem>()
  for (const item of metricsResponse.data.items) {
    metricsMap.set(item.topologyId, item)
  }

  let topologies: Topology[] = topologiesResponse.data.items.map(topo => {
    const metrics = metricsMap.get(topo._id)
    return {
      id: topo._id,
      name: topo.name,
      enabled: topo.enabled,
      processesRun: metrics?.count ?? 0,
      failedProcesses: metrics?.failedCount ?? 0,
      lastRunTime: metrics ? formatDateTime(metrics.created) : '',
      lastRunStatus: metrics ? mapApiStatusToUiStatus(metrics.status) : 'none' as const,
    }
  })

  if (params.status === 'enabled') {
    topologies = topologies.filter(t => t.enabled)
  } else if (params.status === 'with-activity') {
    topologies = topologies.filter(t => t.processesRun > 0)
  }

  const sortField = params.sort || 'name'
  const sortDir = params.order === 'desc' ? -1 : 1
  topologies.sort((a, b) => {
    const av = a[sortField as keyof Topology] ?? ''
    const bv = b[sortField as keyof Topology] ?? ''
    if (typeof av === 'number' && typeof bv === 'number') return (av - bv) * sortDir
    return String(av).localeCompare(String(bv)) * sortDir
  })

  const page = params.page || 1
  const limit = params.limit || 25
  const totalItems = topologies.length
  const totalPages = Math.max(1, Math.ceil(totalItems / limit))
  const paged = topologies.slice((page - 1) * limit, page * limit)

  return {
    data: paged,
    meta: { totalItems, totalPages, currentPage: page, itemsPerPage: limit },
  }
}

/**
 * Fetch topology detail from API
 *
 * @param topologyId - The topology ID
 * @param versionId - Optional specific version ID to load (loads that version's detail instead)
 * @returns Topology detail
 */
export async function fetchTopologyDetail(
  topologyId: string,
  versionId?: string
): Promise<TopologyDetail> {
  // When a specific version is requested, load that version's detail directly
  const id = versionId || topologyId

  const response = await api.get<TopologyDetail>(`/api/topologies/${id}`)

  return response.data
}

/**
 * Fetch all versions for a topology by filtering the topologies list by name
 *
 * @param topologyName - The topology name to find versions for
 * @returns List of all versions for the topology, sorted newest first
 */
export async function fetchTopologyVersions(
  topologyName: string
): Promise<TopologyVersion[]> {
  const response = await api.get<TopologyListApiResponse>('/api/topologies')

  const matchingItems = response.data.items.filter(item => item.name === topologyName)

  // Map to TopologyVersion and sort by version descending (newest first)
  return matchingItems
    .map(item => ({
      id: item._id,
      version: String(item.version),
      visibility: item.visibility as 'draft' | 'public',
      status: item.status as 'New' | 'Starting' | 'Running' | 'Stopped',
      enabled: item.enabled,
      created: '',
      updated: '',
    }))
    .sort((a, b) => Number(b.version) - Number(a.version))
}

// ------ Sidebar tree functions ------

/**
 * Fetch categories and topologies from API, build tree structure for sidebar
 */
export async function fetchTopologiesTree(): Promise<TopologiesTreeNode[]> {
  // Fetch both endpoints in parallel
  const [categoriesResponse, topologiesResponse] = await Promise.all([
    api.get<CategoriesApiResponse>('/api/categories'),
    api.get<TopologyListApiResponse>('/api/topologies'),
  ])

  const categories = categoriesResponse.data.items
  const topologies = topologiesResponse.data.items

  // Group topologies by name to count versions
  // Use the entry with the highest version number as the representative
  const topologyByName = new Map<string, { representative: TopologyListApiItem; versionCount: number }>()

  for (const topo of topologies) {
    const existing = topologyByName.get(topo.name)
    if (!existing) {
      topologyByName.set(topo.name, { representative: topo, versionCount: 1 })
    } else {
      existing.versionCount++
      if (topo.version > existing.representative.version) {
        existing.representative = topo
      }
    }
  }

  // Build topology items grouped by category
  const topologiesByCategory = new Map<string | 'root', TopologyItem[]>()

  for (const [, { representative, versionCount }] of topologyByName) {
    const categoryKey = representative.category || 'root'

    if (!topologiesByCategory.has(categoryKey)) {
      topologiesByCategory.set(categoryKey, [])
    }

    topologiesByCategory.get(categoryKey)!.push({
      id: representative._id,
      type: 'topology',
      name: representative.name,
      folderId: representative.category,
      versionCount,
      enabled: representative.enabled,
      visibility: representative.visibility as 'draft' | 'public',
    })
  }

  // Sort topologies within each category alphabetically
  for (const [, items] of topologiesByCategory) {
    items.sort((a, b) => a.name.localeCompare(b.name))
  }

  // Build category map for nesting
  const categoryMap = new Map<string, CategoryApiItem>()
  for (const cat of categories) {
    categoryMap.set(cat._id, cat)
  }

  // Build folder nodes
  const folderNodes = new Map<string, FolderItem>()
  for (const cat of categories) {
    const children = topologiesByCategory.get(cat._id) || []

    folderNodes.set(cat._id, {
      id: cat._id,
      type: 'folder',
      name: cat.name,
      parentFolderId: cat.parent,
      isExpanded: false,
      system: cat.system ?? false,
      children: [...children],
    })
  }

  // Nest child folders under parent folders
  for (const [, folder] of folderNodes) {
    if (folder.parentFolderId && folderNodes.has(folder.parentFolderId)) {
      const parent = folderNodes.get(folder.parentFolderId)!
      parent.children.push(folder)
    }
  }

  // Sort helper: folders first, then topologies, alphabetically within each group
  const sortNodes = (nodes: TopologiesTreeNode[]) => {
    nodes.sort((a, b) => {
      if (a.type === 'folder' && b.type !== 'folder') return -1
      if (a.type !== 'folder' && b.type === 'folder') return 1
      return a.name.localeCompare(b.name)
    })
  }

  // Sort children of every folder (folders first, then topologies, alphabetically)
  for (const [, folder] of folderNodes) {
    sortNodes(folder.children)
  }

  // Build root-level tree: folders without parents + root topologies
  const tree: TopologiesTreeNode[] = []

  // Add root-level topologies (no category)
  const rootTopologies = topologiesByCategory.get('root') || []
  tree.push(...rootTopologies)

  // Add root-level folders (parent === null)
  for (const [, folder] of folderNodes) {
    if (!folder.parentFolderId) {
      tree.push(folder)
    }
  }

  // Sort root level
  sortNodes(tree)

  return tree
}

/**
 * Fetch categories list from API (for folder dropdowns)
 */
export async function fetchCategories(): Promise<FolderItem[]> {
  const response = await api.get<CategoriesApiResponse>('/api/categories')

  return response.data.items.map(cat => ({
    id: cat._id,
    type: 'folder' as const,
    name: cat.name,
    parentFolderId: cat.parent,
    isExpanded: false,
    system: cat.system ?? false,
    children: [],
  }))
}

/**
 * Fetch the breadcrumb folder names for a given category ID.
 * Returns folder names from root to the given category.
 */
export async function fetchCategoryBreadcrumb(categoryId: string): Promise<string[]> {
  const response = await api.get<CategoriesApiResponse>('/api/categories')
  const categories = response.data.items
  const map = new Map<string, CategoryApiItem>()
  for (const cat of categories) {
    map.set(cat._id, cat)
  }

  const path: string[] = []
  let current: CategoryApiItem | undefined = map.get(categoryId)
  while (current) {
    path.unshift(current.name)
    current = current.parent ? map.get(current.parent) : undefined
  }
  return path
}

/**
 * Create a new category (folder)
 */
export async function publishTopology(topologyId: string): Promise<void> {
  await api.post(`/api/topologies/${topologyId}/publish`)
}

export async function toggleTopologyEnabled(topologyId: string, enabled: boolean): Promise<void> {
  await api.patch(`/api/topologies/${topologyId}`, { enabled })
}

export async function createTopology(
  name: string,
  category: string | null = null,
): Promise<{ _id: string }> {
  const response = await api.post('/api/topologies', { name, category })
  return response.data
}

export async function createCategory(
  name: string,
  parent: string | null = null,
): Promise<{ _id: string; name: string; parent: string | null }> {
  const response = await api.post<{ _id: string; name: string; parent: string | null }>(
    '/api/categories',
    { name, parent },
  )
  return response.data
}

/**
 * Rename a category (folder)
 */
export async function renameCategory(
  id: string,
  name: string,
  parent: string | null = null,
): Promise<void> {
  await api.put(`/api/categories/${id}`, { name, parent })
}

/**
 * Delete a category (folder)
 */
export async function deleteCategory(id: string): Promise<void> {
  await api.delete(`/api/categories/${id}`)
}

/**
 * Delete a topology
 */
export async function deleteTopology(id: string): Promise<void> {
  await api.delete(`/api/topologies/${id}`)
}

/**
 * Update a topology (description, category, etc.)
 */
export async function updateTopology(
  id: string,
  data: { description?: string; category?: string | null; mcp_description?: Record<string, unknown> },
): Promise<void> {
  await api.patch(`/api/topologies/${id}`, data)
}

export async function cloneTopology(topologyId: string) {
  const response = await api.post(`/api/topologies/${topologyId}/clone`)
  return response.data
}

export async function fetchTopologySchema(topologyId: string) {
  const response = await api.get(`/api/topologies/${topologyId}/schema.json`)
  return response.data
}

export async function saveTopologySchema(topologyId: string, schema: any) {
  const response = await api.put(`/api/topologies/${topologyId}/schema.json`, schema)
  return response.data
}
