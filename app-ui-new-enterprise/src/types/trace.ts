// Message types
export type MessageRole = 'user' | 'assistant'
export type MessageStatus = 'sending' | 'sent' | 'error'

export interface ChatMessage {
  id: string
  role: MessageRole
  content: string
  // Optional raw (un-HTML-escaped, un-paragraph-wrapped) version of the
  // assistant text. The renderer uses it to detect onboarding action blocks
  // ([shell] / [prompt] / [link]) and the leading [onboarding-stage:..]
  // marker so cards and stage memory work without re-parsing HTML. Set
  // alongside `content` while streaming and finalised in the same tick.
  rawContent?: string
  timestamp: Date
  status?: MessageStatus
  canSave?: boolean  // Only assistant messages with reports can be saved
  // True while the typewriter animation is still appending characters. The
  // chat view uses it to hide save/copy/export action buttons until the
  // assistant message is fully written so the user cannot save half a report.
  streaming?: boolean
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

// Onboarding action blocks emitted by the Trace summariser as inline tagged
// segments inside an assistant message ("[shell] <label>\n<value>"). The
// drawer parses them out of the prose and renders dedicated cards with copy
// buttons; the original prose continues around them. See parseAssistantBody.
export type OnboardingActionKind = 'shell' | 'prompt' | 'link'

export interface OnboardingAction {
  kind: OnboardingActionKind
  label: string
  // For shell / prompt actions: the verbatim command / prompt body the user
  // can copy. For link actions this is empty and `href` is set instead.
  value?: string
  href?: string
}

// Body segments yielded by the parser. The drawer iterates over them in
// order: text segments render as Markdown, action segments as cards.
export type AssistantBodySegment =
  | { kind: 'text'; content: string }
  | { kind: 'action'; action: OnboardingAction }

