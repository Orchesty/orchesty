/**
 * Helpers for remapping `node.action.worker` strings inside a topology JSON
 * schema. Used by Import / Insert flows so a JSON exported from one
 * environment (e.g. local `node-sdk-local`) can be saved into another
 * environment (e.g. production `node-sdk`) without each node ending up with
 * an empty SDK host on the backend (see TopologySchemaUtils::getSchemaObjectFromJson).
 */

interface SchemaNode {
  action?: { worker?: string } & Record<string, unknown>
  [key: string]: unknown
}

interface SchemaShape {
  nodes?: SchemaNode[]
  [key: string]: unknown
}

const isObject = (value: unknown): value is Record<string, unknown> =>
  typeof value === 'object' && value !== null && !Array.isArray(value)

/**
 * Returns a list of distinct `node.action.worker` values from the schema
 * preserving the order of first occurrence. Empty / missing workers are
 * skipped.
 */
export function extractWorkers(schema: Record<string, unknown>): string[] {
  const result: string[] = []
  const seen = new Set<string>()

  const nodes = (schema as SchemaShape).nodes
  if (!Array.isArray(nodes)) return result

  for (const node of nodes) {
    if (!isObject(node)) continue
    const action = (node as SchemaNode).action
    if (!isObject(action)) continue
    const worker = (action as { worker?: unknown }).worker
    if (typeof worker !== 'string' || worker === '') continue
    if (seen.has(worker)) continue
    seen.add(worker)
    result.push(worker)
  }

  return result
}

/**
 * For each source worker name, try to find an installed worker with the
 * exact same name (case-sensitive). Returns the resolved mapping plus a
 * list of source workers that have no auto-match.
 */
export function autoMapWorkers(
  sources: string[],
  available: ReadonlyArray<{ name: string }>,
): { mapping: Record<string, string>; unresolved: string[] } {
  const availableNames = new Set(available.map((w) => w.name))
  const mapping: Record<string, string> = {}
  const unresolved: string[] = []

  for (const source of sources) {
    if (availableNames.has(source)) {
      mapping[source] = source
    } else {
      unresolved.push(source)
    }
  }

  return { mapping, unresolved }
}

/**
 * Returns a deep copy of the schema with each `node.action.worker` rewritten
 * via `mapping`. Workers without an entry in `mapping` (or where the mapped
 * value equals the original) are left untouched, so the helper is safe to
 * call when the user only changed a subset of workers.
 */
export function applyWorkerMapping(
  schema: Record<string, unknown>,
  mapping: Record<string, string>,
): Record<string, unknown> {
  // JSON round-trip is intentional: the input is always JSON-parsed data
  // (no Dates / Maps / functions), and unlike `structuredClone` it transparently
  // strips Vue's reactive Proxies (which would otherwise throw DataCloneError).
  const copy = JSON.parse(JSON.stringify(schema)) as SchemaShape

  if (!Array.isArray(copy.nodes)) return copy as Record<string, unknown>

  for (const node of copy.nodes) {
    if (!isObject(node)) continue
    const action = (node as SchemaNode).action
    if (!isObject(action)) continue
    const current = (action as { worker?: unknown }).worker
    if (typeof current !== 'string' || current === '') continue

    const next = mapping[current]
    if (typeof next === 'string' && next !== '' && next !== current) {
      ;(action as { worker: string }).worker = next
    }
  }

  return copy as Record<string, unknown>
}
