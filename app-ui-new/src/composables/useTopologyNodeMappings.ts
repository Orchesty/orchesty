import { ref, computed } from 'vue'
import type { TopologyNodeMappings } from '@/types/trash'
import { fetchTopologyNodeMappings, fetchFilteredMappings } from '@/services/trashService'

// Shared state across all instances
// allMappings: includes all nodes (all=1), used for ID-to-name resolution
const allMappings = ref<TopologyNodeMappings | null>(null)
// filteredMappings: only active/relevant nodes, used for dropdown population
const filteredMappingsData = ref<TopologyNodeMappings | null>(null)
const isLoading = ref(false)
const isLoaded = ref(false)

// Track IDs we've already checked per type (to avoid infinite refresh loops)
// Separate sets because IDs could theoretically overlap across types
const checkedMissingIds = {
  topologies: new Set<string>(),
  nodes: new Set<string>(),
  applications: new Set<string>()
}

// Debounce timer for refresh
let refreshTimer: ReturnType<typeof setTimeout> | null = null

// Shared loading promise so concurrent callers can await the in-flight request
let loadingPromise: Promise<void> | null = null

async function fetchWithRetry<T>(fn: () => Promise<T>, retries = 2, delayMs = 1000): Promise<T> {
  for (let i = 0; i <= retries; i++) {
    try { return await fn() }
    catch (e) {
      if (i === retries) throw e
      await new Promise(r => setTimeout(r, delayMs * (i + 1)))
    }
  }
  throw new Error('unreachable')
}

export function useTopologyNodeMappings() {
  // Fetch both mapping sets if not already loaded.
  // If another caller is already loading, wait for its promise instead of skipping.
  const loadMappings = async (force = false) => {
    if (isLoaded.value && !force) return
    if (isLoading.value && loadingPromise && !force) return loadingPromise

    isLoading.value = true
    loadingPromise = (async () => {
      const empty = { applications: {}, nodes: {}, topologies: {}, tree: {} }
      const [allResult, filteredResult] = await Promise.allSettled([
        fetchTopologyNodeMappings(),
        fetchFilteredMappings(),
      ])

      if (allResult.status === 'fulfilled') {
        allMappings.value = allResult.value
      } else {
        console.error('Failed to load all topology/node mappings:', allResult.reason)
        allMappings.value = allMappings.value ?? empty
      }

      if (filteredResult.status === 'fulfilled') {
        filteredMappingsData.value = filteredResult.value
      } else {
        console.error('Failed to load filtered topology/node mappings:', filteredResult.reason)
        filteredMappingsData.value = filteredMappingsData.value ?? empty
      }

      isLoaded.value = true
      isLoading.value = false
      loadingPromise = null
    })()
    return loadingPromise
  }

  // Debounced refresh function
  const scheduleRefresh = () => {
    if (refreshTimer) return // Already scheduled

    refreshTimer = setTimeout(async () => {
      await loadMappings(true) // Force refresh
      refreshTimer = null

      // Only remove IDs from their respective sets if they're NOW found in allMappings
      // This prevents infinite loops for IDs that truly don't exist
      checkedMissingIds.topologies.forEach(id => {
        if (allMappings.value?.topologies[id]) {
          checkedMissingIds.topologies.delete(id)
        }
      })

      checkedMissingIds.nodes.forEach(id => {
        if (allMappings.value?.nodes[id]) {
          checkedMissingIds.nodes.delete(id)
        }
      })

      checkedMissingIds.applications.forEach(id => {
        if (allMappings.value?.applications[id]) {
          checkedMissingIds.applications.delete(id)
        }
      })
      // IDs still not found stay in their sets and won't trigger refresh again
    }, 2000) // Wait 2 seconds before refreshing (in case multiple missing IDs)
  }

  // Lookup topology name with fallback to filtered mappings and auto-refresh
  const getTopologyName = (topologyId: string): string => {
    const name = allMappings.value?.topologies[topologyId]
      ?? filteredMappingsData.value?.topologies[topologyId]

    if (!name && allMappings.value && !checkedMissingIds.topologies.has(topologyId)) {
      checkedMissingIds.topologies.add(topologyId)
      scheduleRefresh()
    }

    return name || topologyId
  }

  // Lookup node name with fallback to filtered mappings and auto-refresh
  const getNodeName = (nodeId: string): string => {
    const name = allMappings.value?.nodes[nodeId]
      ?? filteredMappingsData.value?.nodes[nodeId]

    if (!name && allMappings.value && !checkedMissingIds.nodes.has(nodeId)) {
      checkedMissingIds.nodes.add(nodeId)
      scheduleRefresh()
    }

    return name || nodeId
  }

  // Lookup application name with fallback to filtered mappings and auto-refresh
  const getApplicationName = (applicationKey: string): string => {
    const name = allMappings.value?.applications[applicationKey]
      ?? filteredMappingsData.value?.applications[applicationKey]

    if (!name && allMappings.value && !checkedMissingIds.applications.has(applicationKey)) {
      checkedMissingIds.applications.add(applicationKey)
      scheduleRefresh()
    }

    return name || applicationKey
  }

  // Sorted topology options for dropdowns (uses filtered mappings)
  const topologyOptions = computed(() => {
    if (!filteredMappingsData.value) return []

    return Object.entries(filteredMappingsData.value.topologies)
      .map(([id, name]) => ({ value: id, label: name }))
      .sort((a, b) => a.label.localeCompare(b.label))
  })

  // Sorted topology names array (uses filtered mappings)
  const topologyNames = computed(() => {
    if (!filteredMappingsData.value) return []
    return Object.values(filteredMappingsData.value.topologies).sort()
  })

  // Sorted node options for dropdowns (uses filtered mappings)
  const nodeOptions = computed(() => {
    if (!filteredMappingsData.value) return []

    return Object.entries(filteredMappingsData.value.nodes)
      .map(([id, name]) => ({ value: id, label: name }))
      .sort((a, b) => a.label.localeCompare(b.label))
  })

  // Deduplicated node options by display name (uses filtered mappings)
  // Prevents showing "Http Status 200 Connector" 4 times for 4 different nodeIds
  const deduplicatedNodeOptions = computed(() => {
    if (!filteredMappingsData.value) return []

    const uniqueNames = new Set(Object.values(filteredMappingsData.value.nodes))
    return Array.from(uniqueNames)
      .map(name => ({ value: name, label: name }))
      .sort((a, b) => a.label.localeCompare(b.label))
  })

  // Resolve a display name to all matching nodeIds
  const getNodeIdsByName = (name: string): string[] => {
    if (!filteredMappingsData.value) return []
    return Object.entries(filteredMappingsData.value.nodes)
      .filter(([, nodeName]) => nodeName === name)
      .map(([id]) => id)
  }

  // Sorted application options for dropdowns (uses filtered mappings)
  const applicationOptions = computed(() => {
    if (!filteredMappingsData.value) return []

    return Object.entries(filteredMappingsData.value.applications)
      .map(([key, name]) => ({ value: key, label: name }))
      .sort((a, b) => a.label.localeCompare(b.label))
  })

  // Reactive name maps (id -> name) for passing as props to chart components.
  // These are reactive computeds -- when allMappings updates, dependent components re-render.
  const topologyNameMap = computed<Record<string, string>>(() => allMappings.value?.topologies ?? {})
  const nodeNameMap = computed<Record<string, string>>(() => allMappings.value?.nodes ?? {})
  const applicationNameMap = computed<Record<string, string>>(() => allMappings.value?.applications ?? {})

  // Manual refresh function (for user-triggered refresh)
  // User explicitly wants to retry, so clear ALL checked IDs
  const refresh = async () => {
    checkedMissingIds.topologies.clear()
    checkedMissingIds.nodes.clear()
    checkedMissingIds.applications.clear()
    await loadMappings(true)
  }

  return {
    loadMappings,
    refresh,
    getTopologyName,
    getNodeName,
    getApplicationName,
    topologyNameMap,
    nodeNameMap,
    applicationNameMap,
    topologyOptions,
    topologyNames,
    nodeOptions,
    deduplicatedNodeOptions,
    getNodeIdsByName,
    applicationOptions,
    isLoading,
    isLoaded,
    // Expose filteredMappings as 'mappings' for tree lookups in dropdowns
    mappings: filteredMappingsData,
  }
}
