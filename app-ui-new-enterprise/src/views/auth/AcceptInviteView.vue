<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth0 } from '@auth0/auth0-vue'
import AuthLayout from '@/layouts/AuthLayout.vue'
import PasswordInput from '@/components/ui/PasswordInput.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import { verifyResetToken, activateUser, setNewPassword } from '@/services/authService'
import { useToast } from '@/composables/useToast'
import { useCloudMode } from '@/composables/useCloudMode'
import { isAuth0Enabled } from '@/auth/auth0-plugin'
import { STORAGE_KEYS } from '@/config'

interface Props {
  token: string
}

const props = defineProps<Props>()

const router = useRouter()
const { showToast } = useToast()
const { cloudMode } = useCloudMode()
const auth0 = isAuth0Enabled ? useAuth0() : null

const password = ref('')
const email = ref('')
const error = ref('')
const verifying = ref(true)
const verifyFailed = ref(false)
const submitting = ref(false)

onMounted(async () => {
  try {
    const result = await verifyResetToken(props.token)
    email.value = result.email
    verifying.value = false
  } catch {
    verifyFailed.value = true
    verifying.value = false
    showToast('Invalid or expired invite link.', 'error')
  }
})

function handleGoogleInvite() {
  localStorage.setItem(STORAGE_KEYS.PENDING_INVITE_TOKEN, props.token)
  auth0?.loginWithRedirect({
    authorizationParams: { connection: 'google-oauth2', prompt: 'select_account' },
  })
}

function handleGitHubInvite() {
  localStorage.setItem(STORAGE_KEYS.PENDING_INVITE_TOKEN, props.token)
  auth0?.loginWithRedirect({
    authorizationParams: { connection: 'github', prompt: 'select_account' },
  })
}

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

    <div v-else>
      <h1 class="mb-2 text-2xl font-extrabold leading-tight tracking-tight text-gray-900 dark:text-white">
        Create your account
      </h1>
      <p class="mb-4 text-gray-500 dark:text-gray-400">
        You have been invited as <span class="font-medium text-gray-900 dark:text-white">{{ email }}</span>.
      </p>

      <div class="mt-4 space-y-4 sm:mt-6 sm:space-y-6">
        <template v-if="isAuth0Enabled && !cloudMode">
          <div class="space-y-3">
            <button
              type="button"
              class="inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-gray-900 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
              @click="handleGoogleInvite"
            >
              <svg class="mr-2 h-5 w-5" viewBox="0 0 21 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <g clip-path="url(#clip0_google_invite)">
                  <path d="M20.3081 10.2303C20.3081 9.55056 20.253 8.86711 20.1354 8.19836H10.7031V12.0492H16.1046C15.8804 13.2911 15.1602 14.3898 14.1057 15.0879V17.5866H17.3282C19.2205 15.8449 20.3081 13.2728 20.3081 10.2303Z" fill="#3F83F8" />
                  <path d="M10.7019 20.0006C13.3989 20.0006 15.6734 19.1151 17.3306 17.5865L14.1081 15.0879C13.2115 15.6979 12.0541 16.0433 10.7056 16.0433C8.09669 16.0433 5.88468 14.2832 5.091 11.9169H1.76562V14.4927C3.46322 17.8695 6.92087 20.0006 10.7019 20.0006V20.0006Z" fill="#34A853" />
                  <path d="M5.08857 11.9169C4.66969 10.6749 4.66969 9.33008 5.08857 8.08811V5.51233H1.76688C0.348541 8.33798 0.348541 11.667 1.76688 14.4927L5.08857 11.9169V11.9169Z" fill="#FBBC04" />
                  <path d="M10.7019 3.95805C12.1276 3.936 13.5055 4.47247 14.538 5.45722L17.393 2.60218C15.5852 0.904587 13.1858 -0.0287217 10.7019 0.000673888C6.92087 0.000673888 3.46322 2.13185 1.76562 5.51234L5.08732 8.08813C5.87733 5.71811 8.09302 3.95805 10.7019 3.95805V3.95805Z" fill="#EA4335" />
                </g>
                <defs>
                  <clipPath id="clip0_google_invite"><rect width="20" height="20" fill="white" transform="translate(0.5)" /></clipPath>
                </defs>
              </svg>
              Continue with Google
            </button>

            <button
              type="button"
              class="inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-900 hover:bg-gray-100 hover:text-gray-900 focus:z-10 focus:outline-none focus:ring-4 focus:ring-gray-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white dark:focus:ring-gray-700"
              @click="handleGitHubInvite"
            >
              <svg class="mr-2 h-5 w-5 text-gray-900 dark:text-white" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
              </svg>
              Continue with GitHub
            </button>
          </div>

          <div class="flex items-center">
            <div class="h-px w-full bg-gray-200 dark:bg-gray-700"></div>
            <div class="px-5 text-center text-gray-500 dark:text-gray-400">or</div>
            <div class="h-px w-full bg-gray-200 dark:bg-gray-700"></div>
          </div>
        </template>

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
