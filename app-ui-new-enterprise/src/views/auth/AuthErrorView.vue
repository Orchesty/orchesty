<script setup lang="ts">
import { computed } from 'vue'
import { useAuth0 } from '@auth0/auth0-vue'
import { isAuth0Active } from '@/auth/auth0-plugin'
import { useCloudMode } from '@/composables/useCloudMode'
import { STORAGE_KEYS } from '@/config'
import AuthLayout from '@/layouts/AuthLayout.vue'

const auth0 = isAuth0Active() ? useAuth0() : null
const { cloudMode, cloudUrl } = useCloudMode()

// Loop detection: the router pushes us here when the cloud bounce counter
// (`CLOUD_BOUNCE_*` in sessionStorage) trips. We can tell that case from a
// regular "not in this instance" error by checking the same counter. We
// don't clear it — `handleSignOut()` does, so the user can attempt a
// fresh bounce after intentionally going back to sign-in.
const bounceCount = computed(() =>
  Number.parseInt(sessionStorage.getItem(STORAGE_KEYS.CLOUD_BOUNCE_COUNT) ?? '0', 10) || 0,
)
const handoffFailed = computed(() =>
  sessionStorage.getItem(STORAGE_KEYS.CLOUD_HANDOFF_FAILED) === 'true',
)
const isLoopError = computed(() => bounceCount.value > 3 || handoffFailed.value)

function handleSignOut() {
  localStorage.removeItem(STORAGE_KEYS.AUTH_TOKEN)
  localStorage.removeItem(STORAGE_KEYS.AUTH_USER)
  localStorage.removeItem(STORAGE_KEYS.CLOUD_HANDOFF_SESSION)
  sessionStorage.removeItem(STORAGE_KEYS.AUTH0_LOGIN_FAILED)
  sessionStorage.removeItem(STORAGE_KEYS.CLOUD_BOUNCE_AT)
  sessionStorage.removeItem(STORAGE_KEYS.CLOUD_BOUNCE_COUNT)

  // Cloud mode: hand control back to the cloud frontend. The Auth0 SDK is
  // NEVER installed on a cloud instance origin, so we cannot — and must
  // not — call auth0.logout() here. The cloud frontend's /sign-out clears
  // the central Auth0 session and shows the appropriate post-logout UI.
  if (cloudMode.value && cloudUrl.value) {
    sessionStorage.setItem(STORAGE_KEYS.CLOUD_HANDOFF_FAILED, 'true')
    const returnUrl = encodeURIComponent(window.location.origin)
    window.location.href = `${cloudUrl.value}/sign-in?redirect_to=${returnUrl}&handoff_retry=1`
    return
  }

  // Standalone with Auth0: classic Auth0 logout round-trip on the instance
  // origin (this is the only deployment where redirect_uri = origin is
  // registered in Auth0).
  if (isAuth0Active() && auth0) {
    auth0.logout({ logoutParams: { returnTo: window.location.origin } })
    return
  }

  window.location.href = '/sign-in'
}
</script>

<template>
  <AuthLayout>
    <div class="text-center">
      <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
        <svg class="h-7 w-7 text-red-600 dark:text-red-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
        </svg>
      </div>

      <h1 class="mb-2 text-xl font-bold text-gray-900 dark:text-white">
        {{ isLoopError ? 'Cannot connect to this instance' : 'Access denied' }}
      </h1>

      <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
        <template v-if="isLoopError">
          We tried to hand off your cloud session to this instance several
          times in a row and every attempt failed. This usually means the
          instance cannot reach the cloud (or vice-versa) right now. Try
          again in a moment, and if the problem persists let us know.
        </template>
        <template v-else>
          Your account is not registered in this instance.
          If you believe this is an error, contact the instance administrator.
        </template>
      </p>

      <button
        type="button"
        class="w-full rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
        @click="handleSignOut"
      >
        {{ isLoopError ? 'Back to cloud' : 'Back to sign in' }}
      </button>
    </div>
  </AuthLayout>
</template>
