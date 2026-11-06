import { describe, expect, it } from 'vitest'
import { buildRunUrls, buildWebhookUrls, hasResponseDownstream } from './startingPointUrls'

const node = { topologyId: 't1', topologyName: 'my topology', nodeId: 'n1', nodeName: 'start' }

describe('buildRunUrls', () => {
  it('builds async active and strict URLs', () => {
    expect(buildRunUrls('http://sp/', node, false).map((u) => u.url)).toEqual([
      'http://sp/topologies/my%20topology/nodes/start/run-by-name',
      'http://sp/topologies/t1/nodes/n1/run',
    ])
  })

  it('adds sync variants when requested', () => {
    expect(buildRunUrls('http://sp', node, true).filter((u) => u.mode === 'sync').map((u) => u.url)).toEqual([
      'http://sp/topologies/my%20topology/nodes/start/run-sync-by-name',
      'http://sp/topologies/t1/nodes/n1/run-sync',
    ])
  })
})

describe('buildWebhookUrls', () => {
  it('builds token URLs', () => {
    expect(buildWebhookUrls('http://sp', 'topo', 'hook', 'tok', true).map((u) => u.url)).toEqual([
      'http://sp/topologies/topo/nodes/hook/token/tok/run',
      'http://sp/topologies/topo/nodes/hook/token/tok/run-sync',
    ])
  })
})

describe('hasResponseDownstream', () => {
  const nodes = [
    { id: 's', label: 'Event' },
    { id: 'c', label: 'Connector' },
    { id: 'r', label: 'Response' },
    { id: 's2', label: 'Event' },
    { id: 'n', label: 'Connector' },
  ]
  const connections = [
    { source: 's', target: 'c' },
    { source: 'c', target: 'r' },
    { source: 's2', target: 'n' },
  ]

  it('finds a response node through the graph', () => {
    expect(hasResponseDownstream('s', nodes, connections)).toBe(true)
  })

  it('ignores response nodes reachable only from another start', () => {
    expect(hasResponseDownstream('s2', nodes, connections)).toBe(false)
  })
})
