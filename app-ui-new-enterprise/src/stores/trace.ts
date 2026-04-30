// Pinia store for the Trace chat conversation. The chat lives in localStorage
// so navigating away from the Trace view (or reloading the tab) does not throw
// away in-flight audit reports the user has not yet saved as TraceReport
// documents on the backend.
//
// Persistence policy:
//
//   1. Bounded queue: at most MAX_MESSAGES kept (oldest first dropped).
//      Trimmed by pairs (user+assistant) so the visible conversation always
//      starts on a user prompt. The cap is the primary defense against
//      localStorage growth.
//
//   2. Soft byte budget: MAX_PERSISTED_BYTES caps the serialized payload size.
//      Audit reports rendered into HTML can be ~100s of kB each, so a single
//      anomalous response should not blow the 5 MB origin quota. Older
//      messages are evicted (in pairs again) until the payload fits.
//
//   3. Quota fallback: if setItem still throws QuotaExceededError after both
//      caps applied (e.g. one extreme single message), we drop oldest half
//      and retry once. Persistence is best-effort — the in-memory ref stays
//      authoritative even if the localStorage write fails.

import { defineStore } from 'pinia'
import { ref } from 'vue'
import { STORAGE_KEYS } from '@/config'
import type { ChatMessage } from '@/types/trace'

const MAX_MESSAGES = 30
const MAX_PERSISTED_BYTES = 2 * 1024 * 1024
// Trim by pairs so the conversation is always (user, assistant, user, ...).
const TRIM_STEP = 2

interface PersistedChatMessage extends Omit<ChatMessage, 'timestamp'> {
  timestamp: string
}

const fromPersisted = (m: PersistedChatMessage): ChatMessage => ({
  ...m,
  timestamp: new Date(m.timestamp),
})

const toPersisted = (m: ChatMessage): PersistedChatMessage => ({
  ...m,
  timestamp: m.timestamp.toISOString(),
})

const hydrate = (): ChatMessage[] => {
  const raw = localStorage.getItem(STORAGE_KEYS.TRACE_HISTORY)
  if (!raw) return []
  try {
    const parsed = JSON.parse(raw) as PersistedChatMessage[]
    if (!Array.isArray(parsed)) return []
    // Cap on hydrate too — defends against an older app build that wrote a
    // larger array, or against manual edits in DevTools.
    return parsed.slice(-MAX_MESSAGES).map(fromPersisted)
  } catch {
    return []
  }
}

const trimToFit = (list: ChatMessage[]): ChatMessage[] => {
  // Count cap. Drop pairs from the front until we are under the limit. The
  // pair-step keeps the conversation aligned (always starts on a user prompt)
  // at the cost of letting the count dip up to TRIM_STEP - 1 below the cap.
  while (list.length > MAX_MESSAGES) {
    list.splice(0, TRIM_STEP)
  }

  // Byte cap. Serialize once and keep peeling pairs off the front while we
  // exceed budget. Always keep at least the most recent message.
  let payload = JSON.stringify(list.map(toPersisted))
  while (payload.length > MAX_PERSISTED_BYTES && list.length > 1) {
    list.splice(0, Math.min(TRIM_STEP, list.length - 1))
    payload = JSON.stringify(list.map(toPersisted))
  }

  return list
}

const persist = (list: ChatMessage[]): void => {
  const payload = JSON.stringify(list.map(toPersisted))
  try {
    localStorage.setItem(STORAGE_KEYS.TRACE_HISTORY, payload)
  } catch (err) {
    if (err instanceof DOMException && err.name === 'QuotaExceededError') {
      // Last-resort: drop oldest half (in pairs) and retry once. If it still
      // fails we silently give up — losing persistence is better than crashing
      // the chat send flow.
      const dropCount = Math.max(TRIM_STEP, Math.floor(list.length / 2))
      list.splice(0, Math.min(dropCount, list.length - 1))
      try {
        localStorage.setItem(
          STORAGE_KEYS.TRACE_HISTORY,
          JSON.stringify(list.map(toPersisted)),
        )
      } catch {
        // give up — chat keeps working in-memory.
      }
      return
    }
    throw err
  }
}

export const useTraceStore = defineStore('trace', () => {
  const messages = ref<ChatMessage[]>(hydrate())

  const addMessage = (msg: ChatMessage): void => {
    messages.value.push(msg)
    trimToFit(messages.value)
    persist(messages.value)
  }

  // Patch an existing message in place. While `streaming` is true the
  // typewriter animation is still running, so we deliberately skip the
  // localStorage write — every tick would otherwise re-serialize the whole
  // history (~kBs) ~40 times per second. The final tick passes
  // `streaming: false`, which triggers persistence once.
  const updateMessage = (
    id: string,
    patch: Partial<Pick<ChatMessage, 'content' | 'canSave' | 'streaming'>>,
  ): void => {
    const idx = messages.value.findIndex((m) => m.id === id)
    if (idx === -1) return
    const current = messages.value[idx]
    messages.value[idx] = { ...current, ...patch }
    if (patch.streaming !== false) return
    trimToFit(messages.value)
    persist(messages.value)
  }

  const clear = (): void => {
    messages.value = []
    try {
      localStorage.removeItem(STORAGE_KEYS.TRACE_HISTORY)
    } catch {
      // ignore — clear is best-effort.
    }
  }

  return { messages, addMessage, updateMessage, clear }
})
