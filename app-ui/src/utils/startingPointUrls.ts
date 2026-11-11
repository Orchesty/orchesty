// URLs that start a topology through the starting-point service.
//
// Two dimensions:
//  - version: "active" resolves by topology / node NAME to whichever version is
//    currently active (the default to share); "strict" uses Mongo IDs and is
//    pinned to one exact topology version.
//  - mode: "async" returns right after the process starts (correlation_id);
//    "sync" (`run-sync`) waits for the Response node and returns its payload.
//    Sync only makes sense when a Response node is reachable from the start node.

export type RunUrlMode = 'async' | 'sync'
export type RunUrlVersion = 'active' | 'strict'

export interface RunUrl {
  mode: RunUrlMode
  version: RunUrlVersion
  url: string
}

export interface StartNodeRef {
  topologyId: string
  topologyName: string
  /** Backend (Mongo) id of the start node */
  nodeId: string
  nodeName: string
}

export interface GraphNode {
  id: string
  label: string
}

export interface GraphConnection {
  source: string
  target: string
  sourceOutput?: string
}

export const RESPONSE_NODE_LABEL = 'Response'

const trimBase = (base: string): string => base.replace(/\/+$/, '')

export function buildRunUrl(base: string, node: StartNodeRef, mode: RunUrlMode, version: RunUrlVersion): string {
  const root = trimBase(base)
  const suffix = mode === 'sync' ? 'run-sync' : 'run'

  if (version === 'strict') {
    return `${root}/topologies/${node.topologyId}/nodes/${node.nodeId}/${suffix}`
  }

  const topology = encodeURIComponent(node.topologyName)
  const nodeName = encodeURIComponent(node.nodeName)

  return `${root}/topologies/${topology}/nodes/${nodeName}/${suffix}-by-name`
}

/** All start URLs of an event node; sync variants only when the topology can answer synchronously. */
export function buildRunUrls(base: string, node: StartNodeRef, withSync: boolean): RunUrl[] {
  const modes: RunUrlMode[] = withSync ? ['async', 'sync'] : ['async']
  const versions: RunUrlVersion[] = ['active', 'strict']

  return modes.flatMap((mode) => versions.map((version) => ({ mode, version, url: buildRunUrl(base, node, mode, version) })))
}

/** Webhook callback URLs (name + token based, there is no strict variant). */
export function buildWebhookUrls(base: string, topologyName: string, nodeName: string, token: string, withSync: boolean): RunUrl[] {
  const root = `${trimBase(base)}/topologies/${encodeURIComponent(topologyName)}/nodes/${encodeURIComponent(nodeName)}/token/${token}`
  const urls: RunUrl[] = [{ mode: 'async', version: 'active', url: `${root}/run` }]
  if (withSync) {
    urls.push({ mode: 'sync', version: 'active', url: `${root}/run-sync` })
  }

  return urls
}

/**
 * Whether a Response node is reachable from the start node, i.e. the start node has a "main branch"
 * whose result a synchronous caller can receive.
 */
export function hasResponseDownstream(startId: string, nodes: GraphNode[], connections: GraphConnection[]): boolean {
  const labels = new Map(nodes.map((n) => [n.id, n.label]))
  const followers = new Map<string, string[]>()
  for (const c of connections) {
    followers.set(c.source, [...(followers.get(c.source) ?? []), c.target])
  }

  const visited = new Set<string>()
  const queue = [...(followers.get(startId) ?? [])]
  while (queue.length > 0) {
    const id = queue.shift() as string
    if (visited.has(id)) continue
    visited.add(id)
    if (labels.get(id) === RESPONSE_NODE_LABEL) return true
    queue.push(...(followers.get(id) ?? []))
  }

  return false
}

export type SyncIssueKind = 'branching' | 'batch'

export interface SyncIssue {
  kind: SyncIssueKind
  nodeId: string
}

// Nodes that route a message to exactly one of their outputs (yes / no): followers of
// different outputs are alternatives, but followers of one output still get copies.
// A Question resumes along all its followers, so it is a normal node here.
const SINGLE_OUTPUT_LABELS = new Set(['Confirm'])

/**
 * Main branch = nodes from which a Response node is reachable (the same rule the bridge uses
 * to decide which events it reports to a synchronous caller).
 */
export function mainBranch(nodes: GraphNode[], connections: GraphConnection[]): Set<string> {
  const predecessors = new Map<string, string[]>()
  for (const c of connections) {
    predecessors.set(c.target, [...(predecessors.get(c.target) ?? []), c.source])
  }

  const onPath = new Set<string>()
  const queue = nodes.filter((n) => n.label === RESPONSE_NODE_LABEL).map((n) => n.id)
  while (queue.length > 0) {
    const id = queue.shift() as string
    if (onPath.has(id)) continue
    onPath.add(id)
    queue.push(...(predecessors.get(id) ?? []))
  }

  return onPath
}

/**
 * Main-branch shapes for which a synchronous call (`run-sync`) answers with only one of several
 * results: the first event that reaches the caller wins and the rest are dropped.
 *  - branching: a node sends copies of the message to two or more main-branch followers,
 *  - batch: a Batch node splits the message into many.
 */
export function findSyncIssues(nodes: GraphNode[], connections: GraphConnection[]): SyncIssue[] {
  const onPath = mainBranch(nodes, connections)
  const issues: SyncIssue[] = []

  for (const node of nodes) {
    if (!onPath.has(node.id) || node.label === RESPONSE_NODE_LABEL) continue

    if (node.label === 'Batch') {
      issues.push({ kind: 'batch', nodeId: node.id })
      continue
    }

    const perOutput = SINGLE_OUTPUT_LABELS.has(node.label)
    const followers = new Map<string, Set<string>>()
    for (const c of connections) {
      if (c.source !== node.id || !onPath.has(c.target)) continue
      const output = perOutput ? (c.sourceOutput ?? '') : ''
      followers.set(output, (followers.get(output) ?? new Set()).add(c.target))
    }
    if ([...followers.values()].some((targets) => targets.size > 1)) {
      issues.push({ kind: 'branching', nodeId: node.id })
    }
  }

  return issues
}
