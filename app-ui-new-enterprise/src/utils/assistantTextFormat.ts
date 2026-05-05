import DOMPurify from 'dompurify'
import { marked, type Tokens, type TokenizerAndRendererExtension } from 'marked'

/**
 * Shared assistant-text formatter used by TraceView (whole message) and
 * ChatMessage (per-segment after the onboarding action parser splits the
 * message into text + action cards). Both call sites need the exact same
 * pipeline so Markdown rendering, link decoration and paragraph wrapping
 * behave identically.
 *
 * Pipeline:
 *   1. marked.parse(raw) — render Markdown (headings, fenced code, inline
 *      code, lists, bold/italic, links, GFM autolinks for bare URLs).
 *      `breaks: true` keeps single-newline soft breaks behaving like the
 *      legacy <br> wrapping; `gfm: true` enables tables and autolinking.
 *   2. DOMPurify.sanitize — strip anything not on the allowlist so an LLM
 *      that emits raw <script>/<iframe> can never reach the DOM. The
 *      `afterSanitizeAttributes` hook decorates every <a> with
 *      target="_blank", rel="noopener noreferrer" and the Trace link
 *      classes (matching how the legacy linkifier used to render them).
 *
 * The audit-report HTML and onboarding action cards are rendered via their
 * own templates and never flow through this function — see TraceView's
 * `formatAssistantContent` (audit-report branch) and ChatMessage
 * (`OnboardingActionCard`).
 */

const LINK_CLASSES = 'text-primary-600 hover:underline dark:text-primary-500'

// DOMPurify hooks are global; register the link decoration once on first
// import so we don't keep stacking duplicates if the module is hot-reloaded.
let hookRegistered = false
const ensureLinkHook = () => {
  if (hookRegistered) return
  DOMPurify.addHook('afterSanitizeAttributes', (node) => {
    if (node.nodeName !== 'A') return
    node.setAttribute('target', '_blank')
    node.setAttribute('rel', 'noopener noreferrer')
    const existing = (node.getAttribute('class') || '').trim()
    const merged = existing === '' ? LINK_CLASSES : `${existing} ${LINK_CLASSES}`
    node.setAttribute('class', merged)
  })
  hookRegistered = true
}

// Marked configuration is module-scoped so we only mutate the global parser
// once. `breaks` preserves single-newline soft breaks the way the legacy
// formatter did (\n → <br> inside paragraphs). `gfm` enables autolinking of
// bare URLs so we keep parity with the old `linkifyEscapedHtml` pass.
marked.setOptions({ breaks: true, gfm: true, async: false })

// GFM-style callout block extension. Lets onboarding markdown authors write
//
//     > [!NOTE]
//     > Body of the callout, can span multiple lines and include **markdown**.
//
// and have it rendered as a `<blockquote class="trace-callout trace-callout-note">`
// with a labelled title row, matching the GitHub / Obsidian / MkDocs convention.
// We intentionally do NOT install `marked-alert` — the syntax is small enough
// that a hand-rolled extension keeps the dependency surface stable.
//
// Supported types map 1:1 to the GitHub Flavored Markdown alert spec:
//   note | tip | info | important | warning | caution
//
// Anything else falls through and is rendered as a regular blockquote, so
// future additions stay backwards-compatible until the extension learns them.
const CALLOUT_TYPES = ['note', 'tip', 'info', 'important', 'warning', 'caution'] as const

type CalloutType = (typeof CALLOUT_TYPES)[number]

const CALLOUT_TITLES: Record<CalloutType, string> = {
  note: 'Note',
  tip: 'Tip',
  info: 'Info',
  important: 'Important',
  warning: 'Warning',
  caution: 'Caution',
}

interface CalloutToken extends Tokens.Generic {
  type: 'callout'
  raw: string
  calloutType: CalloutType
  tokens: Tokens.Generic[]
}

const calloutExtension: TokenizerAndRendererExtension = {
  name: 'callout',
  level: 'block',
  start(src: string) {
    const idx = src.search(/(^|\n)>\s*\[!/i)

    return idx === -1 ? undefined : idx
  },
  tokenizer(src: string) {
    // Capture the [!TYPE] header line plus every subsequent `>`-prefixed
    // line until the first non-blockquote line (or EOF). We then strip the
    // `> ` markers and re-tokenise the inner body so authors can use lists,
    // bold, links, even nested code spans inside the callout.
    const match = src.match(/^>\s*\[!([A-Za-z]+)\][^\n]*\n((?:>[^\n]*(?:\n|$))*)/)
    if (!match) return undefined

    const typeRaw = match[1]!
    const bodyRaw = match[2]!
    const type = typeRaw.toLowerCase()
    if (!(CALLOUT_TYPES as readonly string[]).includes(type)) return undefined

    const body = bodyRaw
      .split('\n')
      .map((line) => line.replace(/^>\s?/, ''))
      .join('\n')
      .trim()

    const token: CalloutToken = {
      type: 'callout',
      raw: match[0],
      calloutType: type as CalloutType,
      tokens: [],
    }

    this.lexer.blockTokens(body, token.tokens)

    return token
  },
  renderer(token) {
    const callout = token as CalloutToken
    const title = CALLOUT_TITLES[callout.calloutType]
    const body = this.parser.parse(callout.tokens)

    return [
      `<blockquote class="trace-callout trace-callout-${callout.calloutType}">`,
      `<p class="trace-callout-title">${title}</p>`,
      body,
      '</blockquote>',
    ].join('')
  },
}

marked.use({ extensions: [calloutExtension] })

// `Parameters<typeof DOMPurify.sanitize>[1]` keeps us aligned with whichever
// DOMPurify version is installed without depending on a `Config` namespace
// (DOMPurify v3 stopped re-exporting that namespace).
type SanitizeConfig = NonNullable<Parameters<typeof DOMPurify.sanitize>[1]>

const SANITIZE_CONFIG: SanitizeConfig = {
  ALLOWED_TAGS: [
    'a',
    'p',
    'br',
    'strong',
    'em',
    'b',
    'i',
    'u',
    's',
    'del',
    'code',
    'pre',
    'blockquote',
    'hr',
    'ul',
    'ol',
    'li',
    'h1',
    'h2',
    'h3',
    'h4',
    'h5',
    'h6',
    'table',
    'thead',
    'tbody',
    'tr',
    'th',
    'td',
    'span',
  ],
  ALLOWED_ATTR: ['href', 'title', 'class', 'target', 'rel'],
  // Drop unknown protocols (data:, javascript:, vbscript:) so an LLM cannot
  // sneak a payload through a Markdown link.
  ALLOWED_URI_REGEXP: /^(?:(?:https?|mailto|tel):|[/#?])/i,
}

/**
 * Render an assistant message body as safe HTML. The output may contain
 * Markdown structures (headings, fenced code, lists, inline code, links,
 * GFM autolinks); audit reports and onboarding action cards bypass this
 * function entirely.
 */
export const formatAssistantText = (raw: string): string => {
  if (typeof raw !== 'string' || raw === '') return '<p></p>'

  ensureLinkHook()

  // marked.parse returns string when async:false; cast keeps TypeScript happy
  // without forcing every call site through a Promise.
  const html = marked.parse(raw) as string
  // DOMPurify.sanitize can be typed as `string | TrustedHTML` depending on
  // the DOM env; we pin RETURN_TRUSTED_TYPE: false (default) and coerce so
  // call sites can keep treating the result as a plain string.
  const sanitized = String(DOMPurify.sanitize(html, SANITIZE_CONFIG)).trim()
  return sanitized === '' ? '<p></p>' : sanitized
}
