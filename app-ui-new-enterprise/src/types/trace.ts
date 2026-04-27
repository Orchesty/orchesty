// Message types
export type MessageRole = 'user' | 'assistant'
export type MessageStatus = 'sending' | 'sent' | 'error'

export interface ChatMessage {
  id: string
  role: MessageRole
  content: string
  timestamp: Date
  status?: MessageStatus
  canSave?: boolean  // Only assistant messages with reports can be saved
}

// Report types
export interface TraceReport {
  id: string
  title: string
  content: string
  timestamp: Date
  messageId: string  // Reference to original chat message
}

export interface ReportsByDate {
  date: string  // Format: YYYY-MM-DD
  reports: TraceReport[]
}

// Entity history (per-entity Trace MCP response)
//
// New shape (entry / steps[] / exit) drives the UI cards. `payload` is the
// fields-allowlisted subset extracted by the bridge from the request body that
// flowed through the audit checkpoint. May be null (marker-only nodes with
// `fields: []`) or carry a `_truncated` flag when the size limit kicked in.
//
// Delivery status (resultCode / resultStatus / resultMessage / httpStatus) is
// emitted by the bridge AFTER the connector's processAction returns, so it
// reflects the actual delivery outcome (success / failed / repeating / limit /
// trashed). For passthrough AuditCheckpointNode steps it almost always says
// `success` because the marker node itself cannot fail; meaningful status
// values appear on entry/exit when the connector overrides getAuditCheckpoint().
export type AuditResultStatus =
  | 'success'
  | 'failed'
  | 'repeat'
  | 'trashed'
  | 'limit'
  | 'unknown'

export interface ICheckpointSnapshot {
  time: string | null
  nodeName: string | null
  payload: unknown
  resultCode: number | null
  resultStatus: AuditResultStatus | string | null
  resultMessage: string | null
  httpStatus: number | null
}

// Status derived from bridge progress counters (TopologyProgress.ok / nok /
// finishedAt). Surfaced separately from the per-checkpoint resultStatus so
// the FE can fall back to a meaningful pill when the topology emits no
// audit checkpoints (the common case today).
export type ProgressStatus = 'success' | 'failed' | 'running' | 'unknown'

export interface IEntityRun {
  correlationId: string
  topologyId: string | null
  topologyName: string | null
  entry: ICheckpointSnapshot | null
  steps: ICheckpointSnapshot[]
  exit: ICheckpointSnapshot | null
  // Progress snapshot (same numbers the dashboard process detail shows).
  // Optional/nullable so older backend payloads still render.
  startedAt?: string | null
  finishedAt?: string | null
  ok?: number
  nok?: number
  total?: number
  progressStatus?: ProgressStatus
}

export interface EntityHistoryResponse {
  entity: string
  identifier: Record<string, string>
  // Union of every identifier registered for the matched audit-data rows
  // (the same pairing table used to resolve correlationIds). Carries the
  // full identifier set known for the entity instance(s); empty/undefined
  // when no audit-data row matches the query or for older backend versions.
  identifiers?: Record<string, string>
  runs: IEntityRun[]
}

/** @deprecated kept as alias for older imports — use IEntityRun. */
export type EntityRun = IEntityRun
/** @deprecated kept as alias for older imports — use ICheckpointSnapshot. */
export type EntityCheckpointSnapshot = ICheckpointSnapshot

