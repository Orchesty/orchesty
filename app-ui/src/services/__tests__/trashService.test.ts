import { describe, it, expect, vi, beforeEach } from 'vitest'
import api from '../api'
import { fetchTrashItem } from '../trashService'

vi.mock('../api', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn() },
}))

const getMock = vi.mocked(api.get)

function detailResponse(overrides: Record<string, unknown> = {}) {
  return {
    data: {
      id: 'trash-1',
      nodeId: 'node-1',
      nodeName: 'flexi-bee-get-registers',
      topologyId: 'topo-1',
      topologyName: 'flexi-bee-to-wflow-registers',
      correlationId: 'corr-1',
      type: 'trash',
      created: '2026-10-21 08:14:03',
      updated: '2026-10-21 08:14:03',
      user: null,
      auditLogs: [],
      message: {
        body: '{"foo":"bar","count":2}',
        headers: { 'result-message': 'Request failed with status code 503', 'result-code': '1006' },
      },
      ...overrides,
    },
  }
}

describe('fetchTrashItem', () => {
  beforeEach(() => {
    getMock.mockReset()
  })

  it('maps the nested message body and headers to a TrashItem', async () => {
    getMock.mockResolvedValue(detailResponse())

    const item = await fetchTrashItem('trash-1')

    expect(getMock).toHaveBeenCalledWith('/api/user-task/trash-1')
    expect(item).toEqual({
      id: 'trash-1',
      nodeId: 'node-1',
      topologyId: 'topo-1',
      correlationId: 'corr-1',
      timestamp: '2026-10-21 08:14:03',
      topology: 'topo-1',
      node: 'node-1',
      headers: { 'result-message': 'Request failed with status code 503', 'result-code': '1006' },
      body: { foo: 'bar', count: 2 },
    })
  })

  it('normalises empty headers serialised as an array to an object', async () => {
    getMock.mockResolvedValue(detailResponse({ message: { body: '{}', headers: [] } }))

    const item = await fetchTrashItem('trash-1')

    expect(item?.headers).toEqual({})
    expect(item?.body).toEqual({})
  })

  it('falls back to an empty body when it is not valid JSON', async () => {
    const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {})
    getMock.mockResolvedValue(detailResponse({ message: { body: 'not json', headers: {} } }))

    const item = await fetchTrashItem('trash-1')

    expect(item?.body).toEqual({})
    consoleError.mockRestore()
  })

  it('returns null for a user task that is not a trash item', async () => {
    getMock.mockResolvedValue(detailResponse({ type: 'userTask' }))

    await expect(fetchTrashItem('trash-1')).resolves.toBeNull()
  })

  it('encodes the id in the URL', async () => {
    getMock.mockResolvedValue(detailResponse())

    await fetchTrashItem('a/b?c')

    expect(getMock).toHaveBeenCalledWith('/api/user-task/a%2Fb%3Fc')
  })

  it('propagates request errors', async () => {
    const error = new Error('Request failed with status code 500')
    getMock.mockRejectedValue(error)

    await expect(fetchTrashItem('missing')).rejects.toBe(error)
  })
})
