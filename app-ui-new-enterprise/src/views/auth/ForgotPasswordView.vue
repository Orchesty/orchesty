<script setup lang="ts">
import { ref } from 'vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { isAuth0Enabled } from '@/auth/auth0-plugin'
import { requestPasswordReset as auth0ResetPassword } from '@/services/auth0Service'
import { resetPassword as legacyResetPassword } from '@/services/authService'

const email = ref('')
const error = ref('')
const success = ref(false)
const submitting = ref(false)

async function handleSubmit() {
  error.value = ''
  success.value = false
  submitting.value = true
  try {
    if (isAuth0Enabled) {
      await auth0ResetPassword(email.value)
    } else {
      await legacyResetPassword(email.value)
    }
    success.value = true
  } catch (err: any) {
    error.value = err.message || 'Failed to send reset email. Please try again.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <!-- Success State -->
    <div v-if="success" class="space-y-6 text-center">
      <div class="flex justify-center">
        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900/30">
          <svg class="h-8 w-8 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
          </svg>
        </div>
      </div>

      <div>
        <h1 class="mb-2 text-2xl font-extrabold leading-tight tracking-tight text-gray-900 dark:text-white">
          Check your email
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-400">
          We've sent password reset instructions to <strong class="text-gray-900 dark:text-white">{{ email }}</strong>.
          Please check your inbox.
        </p>
      </div>

      <p class="text-sm text-gray-500 dark:text-gray-400">
        <RouterLink to="/sign-in" class="font-medium text-primary-700 hover:underline dark:text-primary-500">
          Back to Sign in
        </RouterLink>
      </p>
    </div>

    <!-- Form State -->
    <div v-else>
      <h1 class="mb-2 text-2xl font-extrabold leading-tight tracking-tight text-gray-900 dark:text-white">
        Reset your password
      </h1>
      <p class="text-gray-500 dark:text-gray-400">
        We'll email you instructions to reset your password.
      </p>

      <form @submit.prevent="handleSubmit" class="mt-4 sm:mt-6">
        <div v-if="error" class="mb-4 rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/30 dark:text-red-400" role="alert">
          {{ error }}
        </div>

        <div class="mb-6">
          <label for="email" class="mb-2 block text-sm font-medium text-gray-900 dark:text-white">Email address</label>
          <input
            v-model="email"
            type="email"
            id="email"
            class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder:text-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500 sm:text-sm"
            placeholder="name@company.com"
            required
          />
        </div>

        <button
          type="submit"
          :disabled="submitting"
          class="mb-4 w-full rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
        >
          <span v-if="submitting" class="inline-flex items-center">
            <svg class="mr-2 h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Sending...
          </span>
          <span v-else>Send reset email</span>
        </button>

        <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
          <RouterLink to="/sign-in" class="font-medium text-primary-700 hover:underline dark:text-primary-500">
            Back to Sign in
          </RouterLink>
        </p>
      </form>
    </div>
  </AuthLayout>
</template>
