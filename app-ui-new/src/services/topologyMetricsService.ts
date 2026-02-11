import type { TopologyMetrics } from '@/types/topology-metrics'
import metricsData from '@/assets/mock-data/topology-metrics-data.json'

export const fetchTopologyMetrics = async (topologyId: string): Promise<TopologyMetrics> => {
  // Simulate API delay
  await new Promise(resolve => setTimeout(resolve, 300))
  
  const metrics = metricsData[topologyId as keyof typeof metricsData]
  
  if (!metrics) {
    throw new Error(`Metrics not found for topology: ${topologyId}`)
  }
  
  return metrics
}

