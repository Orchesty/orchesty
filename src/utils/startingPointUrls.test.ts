import { describe, expect, it } from 'vitest'
import { buildRunUrls, buildWebhookUrls, findSyncIssues, hasResponseDownstream } from './startingPointUrls'

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

describe('findSyncIssues', () => {
  const n = (id: string, label: string) => ({ id, label })
  const c = (source: string, target: string, sourceOutput?: string) => ({ source, target, sourceOutput })

  it('accepts a linear main branch with a side notification branch', () => {
    const nodes = [n('e', 'Event'), n('a', 'Connector'), n('r', 'Response'), n('s', 'Connector')]
    expect(findSyncIssues(nodes, [c('e', 'a'), c('a', 'r'), c('a', 's')])).toEqual([])
  })

  it('reports a node that fans out into the main branch', () => {
    const nodes = [n('e', 'Event'), n('a', 'Connector'), n('b', 'Connector'), n('r', 'Response')]
    expect(findSyncIssues(nodes, [c('e', 'a'), c('e', 'b'), c('a', 'r'), c('b', 'r')])).toEqual([
      { kind: 'branching', nodeId: 'e' },
    ])
  })

  it('ignores Confirm outputs and reports Batch', () => {
    const nodes = [n('e', 'Event'), n('q', 'Confirm'), n('b', 'Batch'), n('x', 'Connector'), n('r', 'Response')]
    const conns = [c('e', 'q'), c('q', 'b', 'yes'), c('q', 'x', 'no'), c('b', 'r'), c('x', 'r')]
    expect(findSyncIssues(nodes, conns)).toEqual([{ kind: 'batch', nodeId: 'b' }])
  })

  it('reports a Confirm output that fans out', () => {
    const nodes = [n('e', 'Event'), n('q', 'Confirm'), n('a', 'Connector'), n('b', 'Connector'), n('r', 'Response')]
    const conns = [c('e', 'q'), c('q', 'a', 'yes'), c('q', 'b', 'yes'), c('a', 'r'), c('b', 'r')]
    expect(findSyncIssues(nodes, conns)).toEqual([{ kind: 'branching', nodeId: 'q' }])
  })

  it('reports a Question that fans out (it resumes along every follower)', () => {
    const nodes = [n('e', 'Event'), n('q', 'Question'), n('a', 'Connector'), n('b', 'Connector'), n('r', 'Response')]
    const conns = [c('e', 'q'), c('q', 'a'), c('q', 'b'), c('a', 'r'), c('b', 'r')]
    expect(findSyncIssues(nodes, conns)).toEqual([{ kind: 'branching', nodeId: 'q' }])
  })

  it('reports nothing without a Response node', () => {
    const nodes = [n('e', 'Event'), n('b', 'Batch'), n('a', 'Connector')]
    expect(findSyncIssues(nodes, [c('e', 'b'), c('e', 'a')])).toEqual([])
  })
})
