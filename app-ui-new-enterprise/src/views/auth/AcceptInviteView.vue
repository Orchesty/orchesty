<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AuthLayout from '@/layouts/AuthLayout.vue'
import PasswordInput from '@/components/ui/PasswordInput.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import { verifyResetToken, activateUser, setNewPassword } from '@/services/authService'
import { useToast } from '@/composables/useToast'

interface Props {
  token: string
}

const props = defineProps<Props>()

const router = useRouter()
const { showToast } = useToast()

const password = ref('')
const email = ref('')
const error = ref('')
const verifying = ref(true)
const verifyFailed = ref(false)
const submitting = ref(false)
const cloudMode = ref(false)

async function fetchCloudMode(): Promise<void> {
  try {
    const baseURL = import.meta.env.VITE_BACKEND_URL || ''
    const res = await fetch(`${baseURL}/api/status`, {
      headers: { 'Accept': 'application/json' },
    })
    if (res.ok) {
      const data = await res.json()
      cloudMode.value = data.cloudMode === true
    }
  } catch {
    // default false
  }
}

onMounted(async () => {
  try {
    const [result] = await Promise.all([
      verifyResetToken(props.token),
      fetchCloudMode(),
    ])
    email.value = result.email
    verifying.value = false
  } catch {
    verifyFailed.value = true
    verifying.value = false
    showToast('Invalid or expired invite link.', 'error')
  }
})

async function handlePasswordSubmit() {
  if (submitting.value || !password.value || password.value.length < 8) return

  error.value = ''
  submitting.value = true
  try {
    await activateUser(props.token)
    await setNewPassword(props.token, password.value)
    showToast('Account created successfully. You can now sign in.', 'success')
    router.push('/sign-in')
  } catch {
    error.value = 'Failed to create account. Please try again.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <div v-if="verifying" class="flex justify-center py-12">
      <LoadingSpinner message="Verifying your invite link..." />
    </div>

    <template v-else-if="verifyFailed">
      <h1 class="mb-2 text-2xl font-extrabold leading-tight tracking-tight text-gray-900 dark:text-white">
        Invalid invite link
      </h1>
      <p class="mb-6 text-gray-500 dark:text-gray-400">
        This invite link is invalid or has already been used. Please ask your administrator for a new invitation.
      </p>
    </template>

    <!-- Cloud mode: invitations are handled through cloud portal -->
    <template v-else-if="cloudMode">
      <h1 class="mb-2 text-2xl font-extrabold leading-tight tracking-tight text-gray-900 dark:text-white">
        Invitation handled via cloud
      </h1>
      <p class="mb-4 text-gray-500 dark:text-gray-400">
        Invitations for this instance are managed through the cloud portal.
        Please use the invite link you received, which will direct you to the cloud sign-in.
      </p>
      <router-link
        to="/sign-in"
        class="inline-flex items-center text-sm font-medium text-primary-600 hover:underline dark:text-primary-500"
      >
        Go to sign in
        <svg class="ml-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </router-link>
    </template>

    <!-- On-prem mode: standard password creation form -->
    <div v-else>
      <h1 class="mb-2 text-2xl font-extrabold leading-tight tracking-tight text-gray-900 dark:text-white">
        Create your account
      </h1>
      <p class="mb-4 text-gray-500 dark:text-gray-400">
        You have been invited as <span class="font-medium text-gray-900 dark:text-white">{{ email }}</span>.
      </p>

      <div class="mt-4 space-y-4 sm:mt-6 sm:space-y-6">
        <form @submit.prevent="handlePasswordSubmit" class="space-y-4">
          <div v-if="error" class="rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/30 dark:text-red-400" role="alert">
            {{ error }}
          </div>

          <div>
            <PasswordInput
              v-model="password"
              label="Password"
              placeholder="At least 8 characters"
              :show-strength="true"
              required
            />
          </div>

          <button
            type="submit"
            :disabled="submitting || !password || password.length < 8"
            class="w-full rounded-lg bg-primary-700 px-5 py-2.5 text-center text-sm font-medium text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"
          >
            <span v-if="submitting" class="inline-flex items-center">
              <svg class="mr-2 h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Creating account...
            </span>
            <span v-else>Create account</span>
          </button>
        </form>
      </div>
    </div>
  </AuthLayout>
</template>
