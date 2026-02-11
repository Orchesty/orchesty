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

export function useTopologyNodeMappings() {
  // Fetch both mapping sets if not already loaded
  const loadMappings = async (force = false) => {
    if ((isLoaded.value || isLoading.value) && !force) return

    isLoading.value = true
    try {
      const [all, filtered] = await Promise.all([
        fetchTopologyNodeMappings(),
        fetchFilteredMappings(),
      ])
      allMappings.value = all
      filteredMappingsData.value = filtered
      isLoaded.value = true
    } catch (error) {
      console.error('Failed to load topology/node mappings:', error)
      // Set empty mappings to prevent repeated failures
      const empty = { applications: {}, nodes: {}, topologies: {}, tree: {} }
      allMappings.value = empty
      filteredMappingsData.value = empty
    } finally {
      isLoading.value = false
    }
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

  // Lookup topology name with auto-refresh (uses all=1 mappings)
  const getTopologyName = (topologyId: string): string => {
    if (!allMappings.value) return topologyId

    const name = allMappings.value.topologies[topologyId]

    // If not found and we haven't checked this topology ID before
    if (!name && !checkedMissingIds.topologies.has(topologyId)) {
      checkedMissingIds.topologies.add(topologyId)
      scheduleRefresh()
    }

    return name || topologyId
  }

  // Lookup node name with auto-refresh (uses all=1 mappings)
  const getNodeName = (nodeId: string): string => {
    if (!allMappings.value) return nodeId

    const name = allMappings.value.nodes[nodeId]

    // If not found and we haven't checked this node ID before
    if (!name && !checkedMissingIds.nodes.has(nodeId)) {
      checkedMissingIds.nodes.add(nodeId)
      scheduleRefresh()
    }

    return name || nodeId
  }

  // Lookup application name with auto-refresh (uses all=1 mappings)
  const getApplicationName = (applicationKey: string): string => {
    if (!allMappings.value) return applicationKey

    const name = allMappings.value.applications[applicationKey]

    // If not found and we haven't checked this application key before
    if (!name && !checkedMissingIds.applications.has(applicationKey)) {
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

  // Sorted application options for dropdowns (uses filtered mappings)
  const applicationOptions = computed(() => {
    if (!filteredMappingsData.value) return []

    return Object.entries(filteredMappingsData.value.applications)
      .map(([key, name]) => ({ value: key, label: name }))
      .sort((a, b) => a.label.localeCompare(b.label))
  })

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
    topologyOptions,
    topologyNames,
    nodeOptions,
    applicationOptions,
    isLoading,
    isLoaded,
    // Expose filteredMappings as 'mappings' for tree lookups in dropdowns
    mappings: filteredMappingsData,
  }
}
