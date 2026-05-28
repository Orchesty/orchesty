export type MetricsMode = 'last-run' | 'average'

export interface NodeProcessTime {
  nodeName: string
  time: number
}

export interface ConnectorRequestTime {
  connectorName: string
  time: number
}

export interface TopologyMetrics {
  nodeProcessTimes: NodeProcessTime[]
  connectorRequestTimes: ConnectorRequestTime[]
}

