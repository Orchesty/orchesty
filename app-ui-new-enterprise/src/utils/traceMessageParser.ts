import type { AssistantBodySegment, OnboardingAction, OnboardingActionKind } from '@/types/trace'

/**
 * Parse a raw assistant message into a hidden stage marker plus an ordered
 * list of body segments (text or onboarding action). Designed for the Trace
 * onboarding flow:
 *
 *   [onboarding-stage:install-tools next=clone-starter]
 *
 *   # Install your tools
 *   Some intro paragraph...
 *
 *   [shell] Verify Node.js version
 *   ```bash
 *   node --version
 *   ```
 *
 *   [link] Download Node.js 20 LTS
 *   https://nodejs.org/en/download
 *
 *   Reply `next` when you're ready to continue.
 *
 * The parser intentionally tolerates partial input (typewriter streaming):
 * an action header without a complete value yet stays as plain text and is
 * re-parsed on the next streaming tick. Returns `null` action segments only
 * once the full value is available, so the cards appear at most once and do
 * not flicker as more characters arrive.
 *
 * Stage marker handling: a leading `[onboarding-stage:<stage>(?: next=<next>)?]`
 * line is captured into the result and stripped from the segments. Callers
 * forward `stage` / `next` to a Pinia store for client-side stage memory.
 *
 * Robustness: when the LLM misbehaves (no marker, malformed action), the
 * parser falls back to treating the whole input as a single text segment.
 * Nothing is silently dropped.
 */

export interface ParsedAssistantBody {
  stage: string | null
  next: string | null
  segments: AssistantBodySegment[]
}

// Tolerantly match the leading stage marker so a stray "next=foo" outside
// the closing `]` doesn't leak into the rendered body when the LLM drifts
// from the prompt's "next= belongs INSIDE the bracket" rule. We accept:
//   [onboarding-stage:foo]
//   [onboarding-stage:foo next=bar]
//   [onboarding-stage:foo, next=bar]            (comma separator inside)
//   [onboarding-stage:foo] next=bar             (next= bleed after `]`)
const STAGE_MARKER_RE = /^\s*\[onboarding-stage:([^\s,\]]+)(?:[\s,]+next=([^\s\]]+))?\]\s*(?:next=([^\s]+))?\s*$/
const ACTION_HEADER_RE = /^\[(shell|prompt|link)\]\s+(.+?)\s*$/
// Fences are 3 or more backticks. The closing fence must have the same
// number of backticks as the opening fence (CommonMark rule), so we capture
// the opening length and match it on close. This lets prompt action bodies
// embed nested ```lang ... ``` blocks without breaking out of the outer
// fence (renderer wraps prompts in a 4-backtick fence; shell stays at 3).
const FENCE_OPEN_RE = /^(`{3,})[\w-]*\s*$/
const buildFenceCloseRe = (openLen: number): RegExp =>
  new RegExp(`^\`{${openLen}}\\s*$`)

interface PendingActionLookahead {
  action: OnboardingAction
  consumedLines: number
}

/**
 * Try to parse a full action block starting at lines[startIdx]. Returns
 * the action plus how many lines were consumed (header + value lines), or
 * null if the block is incomplete (missing closing fence, missing href).
 *
 * Returning null mid-stream keeps the header text as prose so users see
 * the label appear before the card materialises. On the next streaming
 * update, the closing fence (or href) lands and we promote it to a card.
 */
const tryParseAction = (lines: string[], startIdx: number): PendingActionLookahead | null => {
  const headerMatch = lines[startIdx]?.match(ACTION_HEADER_RE)
  if (!headerMatch) return null

  const kind = headerMatch[1] as OnboardingActionKind
  const label = headerMatch[2].trim()
  if (label === '') return null

  if (kind === 'link') {
    // Link expects exactly one URL line after the header (blank lines tolerated).
    let cursor = startIdx + 1
    while (cursor < lines.length && lines[cursor].trim() === '') cursor += 1
    if (cursor >= lines.length) return null
    const href = lines[cursor].trim()
    if (!/^https?:\/\//i.test(href) && !href.startsWith('/')) return null
    return {
      action: { kind: 'link', label, href },
      consumedLines: cursor - startIdx + 1,
    }
  }

  // shell / prompt: expect a fenced code block after the header.
  let cursor = startIdx + 1
  while (cursor < lines.length && lines[cursor].trim() === '') cursor += 1
  if (cursor >= lines.length) return null
  const openMatch = lines[cursor].match(FENCE_OPEN_RE)
  if (!openMatch) return null

  // Capture the opening fence length so we only treat a same-length fence
  // as the close. Inner ```lang ... ``` blocks inside a 4-backtick prompt
  // are then preserved verbatim instead of terminating the outer card.
  const closeRe = buildFenceCloseRe(openMatch[1].length)

  const valueStart = cursor + 1
  let valueEnd = -1
  for (let i = valueStart; i < lines.length; i += 1) {
    if (closeRe.test(lines[i])) {
      valueEnd = i
      break
    }
  }
  if (valueEnd === -1) return null

  const value = lines.slice(valueStart, valueEnd).join('\n')
  if (value.trim() === '') return null

  return {
    action: { kind, label, value },
    consumedLines: valueEnd - startIdx + 1,
  }
}

/**
 * Push a text fragment onto the segment list, merging adjacent text
 * segments so the renderer sees one contiguous block of prose between
 * action cards (formatAssistantContent then handles paragraphs).
 */
const pushText = (segments: AssistantBodySegment[], text: string): void => {
  if (text === '') return
  const last = segments[segments.length - 1]
  if (last && last.kind === 'text') {
    last.content += text
  } else {
    segments.push({ kind: 'text', content: text })
  }
}

export const parseAssistantBody = (raw: string): ParsedAssistantBody => {
  if (typeof raw !== 'string' || raw === '') {
    return { stage: null, next: null, segments: [] }
  }

  const lines = raw.split('\n')

  let stage: string | null = null
  let next: string | null = null
  let cursor = 0

  // Consume the optional leading stage marker (and the blank line after it,
  // if any) before walking the body.
  while (cursor < lines.length && lines[cursor].trim() === '') cursor += 1
  if (cursor < lines.length) {
    const markerMatch = lines[cursor].match(STAGE_MARKER_RE)
    if (markerMatch) {
      stage = markerMatch[1]
      // Capture group 2 = next= inside the bracket (canonical form);
      // group 3 = next= after the closing `]` (tolerant fallback).
      next = markerMatch[2] ?? markerMatch[3] ?? null
      cursor += 1
      if (cursor < lines.length && lines[cursor].trim() === '') cursor += 1
    }
  }

  const segments: AssistantBodySegment[] = []
  let textBuffer: string[] = []
  const flushText = () => {
    if (textBuffer.length === 0) return
    pushText(segments, textBuffer.join('\n'))
    textBuffer = []
  }

  while (cursor < lines.length) {
    const headerMatch = lines[cursor].match(ACTION_HEADER_RE)
    if (headerMatch) {
      const parsed = tryParseAction(lines, cursor)
      if (parsed) {
        flushText()
        segments.push({ kind: 'action', action: parsed.action })
        cursor += parsed.consumedLines
        continue
      }
      // Header found but block is incomplete (mid-stream). Fall through
      // and emit the header as plain text; next streaming tick will retry.
    }
    textBuffer.push(lines[cursor])
    cursor += 1
  }

  flushText()

  return { stage, next, segments }
}
