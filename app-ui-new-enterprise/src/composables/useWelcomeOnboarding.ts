// Tracks whether the dashboard welcome modal should be shown for the current
// user. Source of truth lives on the backend (`/api/onboarding/state`) so the
// dismissal persists across browsers and devices. localStorage is used purely
// as an anti-flicker cache: once a user dismisses the modal anywhere, this
// browser will know without re-fetching the state on every dashboard mount.

import { ref } from 'vue'
import {
  getOnboardingState,
  markWelcomeSeen,
} from '@/services/onboardingService'

const STORAGE_KEY = 'orchesty.welcomeModal.seen.v1'

const isOpen = ref(false)
let initialized = false

function readLocalSeen(): boolean {
  try {
    return localStorage.getItem(STORAGE_KEY) === '1'
  } catch {
    return false
  }
}

function writeLocalSeen(): void {
  try {
    localStorage.setItem(STORAGE_KEY, '1')
  } catch {
    // ignore — storage may be disabled in private mode; we still rely on
    // the server-side flag as the source of truth.
  }
}

export function useWelcomeOnboarding() {
  /**
   * Loads the user's welcome state and opens the modal if the user has
   * never dismissed it. Safe to call multiple times — subsequent calls
   * are no-ops within the same session.
   */
  const ensureLoaded = async (): Promise<void> => {
    if (initialized) return
    initialized = true

    if (readLocalSeen()) {
      isOpen.value = false
      return
    }

    try {
      const state = await getOnboardingState()
      if (state.welcomeSeenAt) {
        // Server says the user has already dismissed it elsewhere — sync
        // the local cache so the next dashboard mount can short-circuit.
        writeLocalSeen()
        isOpen.value = false
      } else {
        isOpen.value = true
      }
    } catch (err) {
      // If the request fails (e.g. brand-new instance the endpoint isn't
      // deployed to yet, transient network blip), default to "not first
      // visit" so we don't risk flashing the modal on every dashboard
      // load when something is wrong with auth/network.
      console.warn('[useWelcomeOnboarding] failed to load state:', err)
      initialized = false
      isOpen.value = false
    }
  }

  /**
   * Marks the modal as dismissed both locally and on the server, then
   * closes it. Always closes in the UI even if the network call fails —
   * a transient backend hiccup should not pin the modal open forever.
   */
  const dismiss = async (): Promise<void> => {
    isOpen.value = false
    writeLocalSeen()

    try {
      await markWelcomeSeen()
    } catch (err) {
      console.warn('[useWelcomeOnboarding] failed to persist dismissal:', err)
    }
  }

  /**
   * Force-opens the modal regardless of local cache or server state.
   * Used by the `?welcome=1` query-string trigger (handy for previewing
   * the modal copy/layout without resetting the dismissal record). Does
   * not touch localStorage or the server flag — dismissing afterwards
   * persists as usual.
   */
  const forceOpen = (): void => {
    initialized = true
    isOpen.value = true
  }

  return {
    isOpen,
    ensureLoaded,
    dismiss,
    forceOpen,
  }
}
