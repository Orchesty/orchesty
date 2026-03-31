<script setup lang="ts">
import { useAuth0 } from '@auth0/auth0-vue'
import { isAuth0Enabled } from '@/auth/auth0-plugin'
import { STORAGE_KEYS } from '@/config'
import AuthLayout from '@/layouts/AuthLayout.vue'

const auth0 = isAuth0Enabled ? useAuth0() : null

function handleSignOut() {
  localStorage.removeItem(STORAGE_KEYS.AUTH_TOKEN)
  localStorage.removeItem(STORAGE_KEYS.AUTH_USER)
  localStorage.removeItem(STORAGE_KEYS.CLOUD_HANDOFF_SESSION)
  sessionStorage.removeItem(STORAGE_KEYS.AUTH0_LOGIN_FAILED)

  if (isAuth0Enabled && auth0) {
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
        Access denied
      </h1>

      <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
        Your account is not registered in this instance.
        If you believe this is an error, contact the instance administrator.
      </p>

      <button
        type="button"
        class="w-full rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
        @click="handleSignOut"
      >
        Back to sign in
      </button>
    </div>
  </AuthLayout>
</template>
