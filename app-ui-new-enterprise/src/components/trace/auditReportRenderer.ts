// Trace Audit Report renderer — produces a self-contained HTML string used
// in three places:
//   - the assistant chat bubble (TraceView.formatAssistantContent),
//   - the saved-report modal (TraceReportModal v-html),
//   - the PDF print window (printReport.ts).
//
// All output is plain HTML with Tailwind classes only, so the same string
// renders identically in the SPA and inside a fresh browser tab loading
// Tailwind from CDN. Layout mirrors the approved Demo template.

import type {
  EntityHistoryResponse,
  ICheckpointSnapshot,
  IEntityRun,
} from '@/types/trace'

export interface ReportMeta {
  generatedAt: Date
  reportId: string
}

export const renderAuditReportHtml = (
  history: EntityHistoryResponse,
  meta: ReportMeta,
): string => {
  const blocks = [
    renderHeader(history, meta),
    renderSummaryCards(history),
    renderIdentifiersTable(history),
    renderProcessFlow(history),
  ].filter((b) => b.length > 0)

  // Force light surface even inside a dark UI — the report is a printable
  // artefact and must read identically wherever it lands. `not-prose`
  // opts out of any surrounding @tailwindcss/typography prose styling
  // so our explicit Tailwind classes win in the chat bubble and modal.
  return `<article class="trace-audit-report not-prose bg-white text-gray-900 text-sm dark:bg-white dark:text-gray-900">
    <div class="max-w-4xl mx-auto">
      ${blocks.join('\n')}
    </div>
  </article>`
}

// ──────────────────────────── Header ────────────────────────────

const renderHeader = (history: EntityHistoryResponse, meta: ReportMeta): string => {
  const entity = escapeHtml(history.entity)
  const queryAttr = renderQueryAttribute(history.identifier)
  const generatedAt = escapeHtml(formatDateTime(meta.generatedAt))
  const reportId = escapeHtml(meta.reportId)

  return `<header class="border-b border-gray-200 pb-4 mb-6">
    <div class="flex justify-between items-start gap-6">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight mb-2">Trace Audit Report</h1>
        <p class="text-sm text-gray-700">
          <span class="font-semibold">Entity type:</span>
          <span>${entity}</span>
        </p>
        ${queryAttr ? `<p class="text-sm text-gray-700">
          <span class="font-semibold">Query attribute:</span>
          <span>${queryAttr}</span>
        </p>` : ''}
      </div>
      <div class="text-right space-y-1">
        <p class="text-xs text-gray-500">
          <span class="font-semibold">Generated at:</span>
          <span>${generatedAt}</span>
        </p>
        <p class="text-xs text-gray-500">
          <span class="font-semibold">Report ID:</span>
          <span class="font-mono">${reportId}</span>
        </p>
      </div>
    </div>
  </header>`
}

const renderQueryAttribute = (identifier: Record<string, string>): string => {
  const entries = Object.entries(identifier ?? {})
  if (entries.length === 0) return ''
  return entries
    .map(([k, v]) => `${escapeHtml(k)} = ${escapeHtml(String(v))}`)
    .join(', ')
}

// ────────────────────── Summary cards (3-up) ────────────────────

const renderSummaryCards = (history: EntityHistoryResponse): string => {
  const total = history.runs.length
  const { first, last } = collectTimeRange(history.runs)

  return `<div class="grid grid-cols-3 gap-4 mb-8">
    ${summaryCard('Total processes', String(total))}
    ${summaryCard('First occurrence', first ? formatTimeNs(first) : '—')}
    ${summaryCard('Last occurrence', last ? formatTimeNs(last) : '—')}
  </div>`
}

const summaryCard = (label: string, value: string): string => `
  <div>
    <p class="text-xs uppercase tracking-wide text-gray-500 mb-1">${escapeHtml(label)}</p>
    <p class="text-base font-semibold">${escapeHtml(value)}</p>
  </div>`

const collectTimeRange = (
  runs: IEntityRun[],
): { first: string | null; last: string | null } => {
  let firstNs: bigint | null = null
  let lastNs: bigint | null = null
  let firstRaw: string | null = null
  let lastRaw: string | null = null

  const consider = (snap: ICheckpointSnapshot | null): void => {
    if (!snap?.time) return
    let ns: bigint
    try {
      ns = BigInt(snap.time)
    } catch {
      return
    }
    if (firstNs === null || ns < firstNs) {
      firstNs = ns
      firstRaw = snap.time
    }
    if (lastNs === null || ns > lastNs) {
      lastNs = ns
      lastRaw = snap.time
    }
  }

  for (const run of runs) {
    consider(run.entry)
    consider(run.exit)
    for (const step of run.steps) consider(step)
  }

  return { first: firstRaw, last: lastRaw }
}

// ──────────────────── Entity identifiers table ──────────────────

const renderIdentifiersTable = (history: EntityHistoryResponse): string => {
  const map: Record<string, string> = {
    ...(history.identifier ?? {}),
    ...(history.identifiers ?? {}),
  }
  const entries = Object.entries(map)
  if (entries.length === 0) return ''

  const rows = entries
    .map(
      ([k, v]) => `<tr>
        <td class="py-2 text-gray-700">${escapeHtml(k)}</td>
        <td class="py-2 font-mono text-gray-900">${escapeHtml(String(v))}</td>
      </tr>`,
    )
    .join('')

  return `<section class="mb-8">
    <h2 class="text-lg font-semibold mb-3">Entity identifiers</h2>
    <div>
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="py-2 text-left font-semibold text-gray-700 w-1/3">Attribute</th>
            <th class="py-2 text-left font-semibold text-gray-700">Value</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">${rows}</tbody>
      </table>
    </div>
  </section>`
}

// ─────────────────────── Process flow overview ──────────────────

const renderProcessFlow = (history: EntityHistoryResponse): string => {
  if (history.runs.length === 0) {
    return `<section class="mb-2">
      <h2 class="text-lg font-semibold mb-3">Process flow overview</h2>
      <p class="text-sm italic text-gray-500">No topology runs touched this entity.</p>
    </section>`
  }

  const cards = history.runs.map(renderRunCard).join('')

  return `<section>
    <h2 class="text-lg font-semibold mb-3">Process flow overview</h2>
    <div class="space-y-5">${cards}</div>
  </section>`
}

const renderRunCard = (run: IEntityRun): string => {
  const status = deriveOverallStatus(run)
  const statusPill = renderOverallStatusPill(status)

  const start = run.entry?.time
  const end = run.exit?.time

  return `<article class="overflow-hidden">
    <header class="py-3 border-b border-gray-200 flex flex-wrap items-center justify-between gap-3">
      <div>
        <p class="text-xs uppercase tracking-wide text-gray-500">Topology</p>
        <p class="text-sm font-semibold">${escapeHtml(run.topologyName ?? 'Unknown topology')}</p>
        ${run.topologyId ? `<p class="text-xs text-gray-500">ID: <span class="font-mono">${escapeHtml(run.topologyId)}</span></p>` : ''}
        <p class="text-xs text-gray-500">Run ID: <span class="font-mono">${escapeHtml(run.correlationId)}</span></p>
      </div>
      <div class="text-right">
        <p class="text-xs text-gray-500 mb-1">
          <span class="font-semibold">Status:</span>${statusPill}
        </p>
        <p class="text-xs text-gray-500">
          <span class="font-semibold">Start:</span>
          <span>${escapeHtml(start ? formatTimeNs(start) : '—')}</span>
        </p>
        <p class="text-xs text-gray-500">
          <span class="font-semibold">End:</span>
          <span>${escapeHtml(end ? formatTimeNs(end) : '—')}</span>
        </p>
      </div>
    </header>

    <div class="py-3 space-y-3">
      ${renderIoBlock('Input data (excerpt)', run.entry, 'No input captured (no process_entry checkpoint).')}
      ${renderIoBlock('Output data (excerpt)', run.exit, 'No output captured (no process_exit checkpoint).')}
      ${renderStepsBlock(run.steps)}
      ${renderRunFailureMessage(run)}
    </div>
  </article>`
}

const renderIoBlock = (
  label: string,
  snap: ICheckpointSnapshot | null,
  emptyText: string,
): string => {
  const heading = `<p class="text-xs uppercase tracking-wide text-gray-500 mb-1">${escapeHtml(label)}</p>`

  if (!snap) {
    return `<div>${heading}<p class="text-xs italic text-gray-500">${escapeHtml(emptyText)}</p></div>`
  }

  return `<div>${heading}${renderPayloadBlock(snap.payload)}</div>`
}

const renderPayloadBlock = (payload: unknown): string => {
  const flags = readPayloadFlags(payload)
  const blocks: string[] = []

  if (flags.truncated) {
    const sizeNote = flags.originalSizeBytes !== null
      ? ` (originalSize: ${flags.originalSizeBytes} B)`
      : ''
    blocks.push(
      `<div class="mb-1 rounded border border-amber-200 bg-amber-50 p-1.5 text-xs text-amber-800">
        Payload překročil limit 64 KB${sizeNote}; zužte allowlist v <code class="font-mono">IAuditCheckpoint.fields</code>.
      </div>`,
    )
  }

  if (payload === null || payload === undefined) {
    blocks.push('<p class="text-xs italic text-gray-500">(marker — bez payloadu)</p>')
    return blocks.join('')
  }

  const text = formatPayloadJson(payload)
  if (text) {
    blocks.push(
      `<pre class="bg-gray-50 border border-gray-200 rounded-md p-3 text-xs leading-snug overflow-x-auto"><code class="font-mono">${escapeHtml(text)}</code></pre>`,
    )
  }

  return blocks.join('')
}

const renderStepsBlock = (steps: ICheckpointSnapshot[]): string => {
  if (!steps || steps.length === 0) return ''

  // Group by nodeName so multi-entity correlations stay readable.
  const grouped = new Map<string, ICheckpointSnapshot[]>()
  for (const step of steps) {
    const name = step.nodeName ?? '(unknown node)'
    const list = grouped.get(name) ?? []
    list.push(step)
    grouped.set(name, list)
  }

  const groupBlocks = Array.from(grouped.entries())
    .map(([name, list]) => {
      const items = list
        .map(
          (step) => `<div class="border-t border-dashed border-gray-100 pt-1 first:border-t-0 first:pt-0">
            <div class="flex items-center justify-between gap-2 text-xs text-gray-600">
              <span><span class="font-semibold">Time:</span> ${escapeHtml(step.time ? formatTimeNs(step.time) : '—')}</span>
              ${renderStatusBadgeHtml(step)}
            </div>
            ${renderResultMessageHtml(step)}
            ${renderPayloadBlock(step.payload)}
          </div>`,
        )
        .join('')

      return `<div class="rounded border border-gray-100 p-1.5">
        <div class="mb-1 truncate text-xs font-semibold text-gray-700">${escapeHtml(name)}</div>
        <div class="space-y-2">${items}</div>
      </div>`
    })
    .join('')

  return `<details class="rounded border border-gray-200">
    <summary class="cursor-pointer select-none px-2 py-1.5 text-xs font-semibold uppercase tracking-wide text-gray-600">
      Audit checkpoints (${steps.length})
    </summary>
    <div class="space-y-2 p-2">${groupBlocks}</div>
  </details>`
}

const renderRunFailureMessage = (run: IEntityRun): string => {
  // Surface the most informative failure reason (exit > entry) at run level.
  const candidate = run.exit && isFailureStatus(run.exit.resultStatus)
    ? run.exit
    : run.entry && isFailureStatus(run.entry.resultStatus)
      ? run.entry
      : null
  return renderResultMessageHtml(candidate)
}

// ────────────────────────── Status helpers ──────────────────────

type OverallStatus = 'success' | 'failed' | 'repeating' | 'unknown'

const deriveOverallStatus = (run: IEntityRun): OverallStatus => {
  // Prefer exit (final delivery) over entry; fall back through steps.
  const candidates: (ICheckpointSnapshot | null)[] = [run.exit, run.entry, ...run.steps]
  for (const snap of candidates) {
    const s = snap?.resultStatus
    if (!s) continue
    if (s === 'failed' || s === 'limit') return 'failed'
    if (s === 'repeat') return 'repeating'
    if (s === 'trashed') return 'failed'
    if (s === 'success') return 'success'
  }
  return 'unknown'
}

const OVERALL_PILL: Record<OverallStatus, { label: string; classes: string }> = {
  success:   { label: 'SUCCESS',   classes: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
  failed:    { label: 'FAILED',    classes: 'bg-red-50 text-red-700 border-red-200' },
  repeating: { label: 'REPEATING', classes: 'bg-amber-50 text-amber-700 border-amber-200' },
  unknown:   { label: 'UNKNOWN',   classes: 'bg-gray-50 text-gray-700 border-gray-200' },
}

const renderOverallStatusPill = (status: OverallStatus): string => {
  const { label, classes } = OVERALL_PILL[status]
  return `<span class="ml-1 px-2 py-0.5 rounded-full text-[11px] font-semibold border ${classes}">${escapeHtml(label)}</span>`
}

interface InlineBadgeStyle { label: string; classes: string }

const INLINE_BADGE: Record<string, InlineBadgeStyle> = {
  success: { label: 'Delivered', classes: 'bg-emerald-100 text-emerald-800' },
  failed:  { label: 'Failed',    classes: 'bg-red-100 text-red-800' },
  repeat:  { label: 'Repeating', classes: 'bg-amber-100 text-amber-800' },
  trashed: { label: 'Trashed',   classes: 'bg-gray-200 text-gray-800' },
  limit:   { label: 'Limit',     classes: 'bg-orange-100 text-orange-800' },
  unknown: { label: 'Unknown',   classes: 'bg-gray-100 text-gray-700' },
}

const renderStatusBadgeHtml = (snap: ICheckpointSnapshot | null): string => {
  if (!snap || !snap.resultStatus) return ''
  const style = INLINE_BADGE[snap.resultStatus] ?? INLINE_BADGE.unknown!
  const titleParts: string[] = []
  if (snap.httpStatus != null) titleParts.push(`HTTP ${snap.httpStatus}`)
  if (snap.resultCode != null) titleParts.push(`code ${snap.resultCode}`)
  const title = titleParts.length > 0
    ? ` title="${escapeHtml(titleParts.join(' \u00B7 '))}"`
    : ''
  return `<span class="ml-1 rounded-full px-2 py-0.5 text-[11px] font-semibold ${style.classes}"${title}>${escapeHtml(style.label)}</span>`
}

const isFailureStatus = (status: string | null | undefined): boolean =>
  status === 'failed' || status === 'repeat' || status === 'limit' || status === 'trashed'

const renderResultMessageHtml = (snap: ICheckpointSnapshot | null): string => {
  if (!snap || !isFailureStatus(snap.resultStatus) || !snap.resultMessage) return ''
  return `<div class="mt-1 rounded border border-red-200 bg-red-50 p-1.5 text-xs text-red-800">
    <span class="font-semibold">Result:</span> ${escapeHtml(snap.resultMessage)}
  </div>`
}

// ─────────────────────── Payload / formatting ───────────────────

interface PayloadFlags {
  truncated: boolean
  invalidJson: boolean
  originalSizeBytes: number | null
}

const readPayloadFlags = (payload: unknown): PayloadFlags => {
  const flags: PayloadFlags = { truncated: false, invalidJson: false, originalSizeBytes: null }
  if (!payload || typeof payload !== 'object') return flags
  const p = payload as Record<string, unknown>
  if (p._truncated === true) flags.truncated = true
  if (p._invalidJson === true) flags.invalidJson = true
  if (typeof p._originalSizeBytes === 'number') flags.originalSizeBytes = p._originalSizeBytes
  return flags
}

// XSS-safe: bridge applies an allowlist + redaction so values are bounded,
// but we still escape every character that lands in the markup.
const formatPayloadJson = (payload: unknown): string => {
  if (payload === null || payload === undefined) return ''
  if (typeof payload === 'string') {
    try {
      return JSON.stringify(JSON.parse(payload), null, 2)
    } catch {
      return payload
    }
  }
  return JSON.stringify(payload, null, 2)
}

// Bridge emits time as a nanosecond Unix timestamp string. Render in the
// user's locale; fall back to the raw value if parsing fails.
export const formatTimeNs = (time: string | null): string => {
  if (!time) return '—'
  let ns: bigint
  try {
    ns = BigInt(time)
  } catch {
    return time
  }
  const ms = Number(ns / 1_000_000n)
  if (!Number.isFinite(ms)) return time
  return formatDateTime(new Date(ms))
}

const formatDateTime = (d: Date): string => {
  const pad = (n: number): string => n.toString().padStart(2, '0')
  const y = d.getFullYear()
  const M = pad(d.getMonth() + 1)
  const D = pad(d.getDate())
  const h = pad(d.getHours())
  const m = pad(d.getMinutes())
  const s = pad(d.getSeconds())
  return `${y}-${M}-${D} ${h}:${m}:${s}`
}

export const escapeHtml = (input: string): string => {
  // Avoid `document` so this module is also safe to use in non-DOM contexts.
  return input
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
}

// Convenience for callers that need a short, stable-ish report ID.
export const makeReportId = (): string => {
  const cryptoApi = (globalThis as { crypto?: Crypto }).crypto
  if (cryptoApi?.randomUUID) {
    return `report_${cryptoApi.randomUUID().replace(/-/g, '').slice(0, 8)}`
  }
  return `report_${Math.random().toString(16).slice(2, 10)}`
}
