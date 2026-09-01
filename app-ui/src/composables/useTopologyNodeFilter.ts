import { ref, computed, watch } from 'vue'
import { useTopologyNodeMappings } from './useTopologyNodeMappings'

/**
 * Provides topology and node filter refs with computed dropdown options
 * and automatic node-filter reset when the selected topology changes.
 */
export function useTopologyNodeFilter() {
  const {
    getTopologyName,
    getNodeName,
    getNodeIdsByName,
    topologyOptions: topologyOptionsFromMappings,
    deduplicatedNodeOptions: deduplicatedNodeOptionsFromMappings,
    mappings,
  } = useTopologyNodeMappings()

  const topologyFilter = ref<string | null>(null)
  const nodeFilter = ref<string | null>(null)

  const topologyOptions = computed(() => [
    { value: null, label: 'All Topologies' },
    ...topologyOptionsFromMappings.value,
  ])

  const nodeOptions = computed(() => {
    const baseOptions = [{ value: null, label: 'All Nodes' }]

    if (!topologyFilter.value || !mappings.value) {
      return [...baseOptions, ...deduplicatedNodeOptionsFromMappings.value]
    }

    const nodeIdsInTopology = mappings.value.topologyTree[topologyFilter.value] || []

    const namesInTopology = new Set(
      nodeIdsInTopology
        .map((id: string) => mappings.value?.nodes[id])
        .filter((name): name is string => !!name),
    )

    const filteredNodes = Array.from(namesInTopology)
      .map(name => ({ value: name, label: name }))
      .sort((a, b) => a.label.localeCompare(b.label))

    return [...baseOptions, ...filteredNodes]
  })

  watch(topologyFilter, () => {
    if (nodeFilter.value && topologyFilter.value && mappings.value) {
      const nodeIdsInTopology = mappings.value.topologyTree[topologyFilter.value] || []
      const namesInTopology = new Set(
        nodeIdsInTopology
          .map((id: string) => mappings.value?.nodes[id])
          .filter(Boolean),
      )

      if (!namesInTopology.has(nodeFilter.value)) {
        nodeFilter.value = null
      }
    }
  })

  return {
    topologyFilter,
    nodeFilter,
    topologyOptions,
    nodeOptions,
    getTopologyName,
    getNodeName,
    getNodeIdsByName,
    mappings,
  }
}
