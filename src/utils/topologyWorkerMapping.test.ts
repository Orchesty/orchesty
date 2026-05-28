import { describe, it, expect } from 'vitest'
import { extractWorkers, autoMapWorkers, applyWorkerMapping } from './topologyWorkerMapping'

describe('extractWorkers', () => {
  it('returns distinct worker names in first-occurrence order', () => {
    const schema = {
      nodes: [
        { id: '1', action: { worker: 'node-sdk' } },
        { id: '2', action: { worker: 'php-sdk' } },
        { id: '3', action: { worker: 'node-sdk' } },
        { id: '4', action: { worker: 'go-sdk' } },
      ],
    }
    expect(extractWorkers(schema)).toEqual(['node-sdk', 'php-sdk', 'go-sdk'])
  })

  it('ignores nodes without action and empty worker strings', () => {
    const schema = {
      nodes: [
        { id: '1' },
        { id: '2', action: {} },
        { id: '3', action: { worker: '' } },
        { id: '4', action: { worker: 'node-sdk' } },
        null,
      ],
    }
    expect(extractWorkers(schema as Record<string, unknown>)).toEqual(['node-sdk'])
  })

  it('returns empty array for missing or non-array nodes', () => {
    expect(extractWorkers({})).toEqual([])
    expect(extractWorkers({ nodes: 'not-an-array' } as unknown as Record<string, unknown>)).toEqual([])
  })
})

describe('autoMapWorkers', () => {
  it('matches by exact (case-sensitive) name', () => {
    const result = autoMapWorkers(
      ['node-sdk', 'php-sdk', 'Node-SDK'],
      [{ name: 'node-sdk' }, { name: 'php-sdk' }, { name: 'go-sdk' }],
    )
    expect(result.mapping).toEqual({ 'node-sdk': 'node-sdk', 'php-sdk': 'php-sdk' })
    expect(result.unresolved).toEqual(['Node-SDK'])
  })

  it('marks every source as unresolved when no workers are installed', () => {
    const result = autoMapWorkers(['node-sdk', 'php-sdk'], [])
    expect(result.mapping).toEqual({})
    expect(result.unresolved).toEqual(['node-sdk', 'php-sdk'])
  })

  it('handles empty sources list', () => {
    const result = autoMapWorkers([], [{ name: 'node-sdk' }])
    expect(result.mapping).toEqual({})
    expect(result.unresolved).toEqual([])
  })
})

interface NodeWithWorker {
  id?: string
  action?: { worker?: string; name?: string }
}

const workersOf = (schema: Record<string, unknown>): Array<string | undefined> => {
  const nodes = schema.nodes as NodeWithWorker[] | undefined
  return (nodes ?? []).map((n) => n.action?.worker)
}

describe('applyWorkerMapping', () => {
  it('rewrites node.action.worker according to the mapping', () => {
    const schema = {
      nodes: [
        { id: '1', action: { worker: 'node-sdk-local', name: 'foo' } },
        { id: '2', action: { worker: 'php-sdk' } },
      ],
      connections: [],
    }
    const result = applyWorkerMapping(schema, { 'node-sdk-local': 'node-sdk' })
    expect(workersOf(result)).toEqual(['node-sdk', 'php-sdk'])
  })

  it('does not mutate the original schema', () => {
    const schema = {
      nodes: [{ id: '1', action: { worker: 'node-sdk-local' } }],
    }
    applyWorkerMapping(schema, { 'node-sdk-local': 'node-sdk' })
    expect(workersOf(schema)).toEqual(['node-sdk-local'])
  })

  it('leaves workers without a mapping entry untouched', () => {
    const schema = {
      nodes: [
        { id: '1', action: { worker: 'node-sdk-local' } },
        { id: '2', action: { worker: 'php-sdk' } },
      ],
    }
    const result = applyWorkerMapping(schema, { 'node-sdk-local': 'node-sdk' })
    expect(workersOf(result)).toEqual(['node-sdk', 'php-sdk'])
  })

  it('skips empty / missing actions and noop mappings', () => {
    const schema = {
      nodes: [
        { id: '1' },
        { id: '2', action: { worker: '' } },
        { id: '3', action: { worker: 'node-sdk' } },
      ],
    }
    const result = applyWorkerMapping(schema, { 'node-sdk': 'node-sdk' })
    expect(workersOf(result)).toEqual([undefined, '', 'node-sdk'])
  })
})
