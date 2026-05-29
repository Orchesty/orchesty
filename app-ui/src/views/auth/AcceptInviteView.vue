<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AuthLayout from '@/layouts/AuthLayout.vue'
import Button from '@/components/ui/Button.vue'
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
const verifying = ref(true)
const verifyFailed = ref(false)
const isLoading = ref(false)

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

const handleSubmit = async () => {
  if (isLoading.value || !password.value || password.value.length < 8) return

  isLoading.value = true
  try {
    await activateUser(props.token)
    await setNewPassword(props.token, password.value)
    showToast('Account created successfully. You can now sign in.', 'success')
    router.push('/sign-in')
  } catch {
    showToast('Failed to create account. Please try again.', 'error')
  } finally {
    isLoading.value = false
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
      <RouterLink
        to="/sign-in"
        class="font-medium text-primary-700 hover:underline dark:text-primary-500"
      >
        Go to Sign in
      </RouterLink>
    </template>

    <!-- Password form -->
    <template v-else>
      <h1 class="mb-2 text-2xl font-extrabold leading-tight tracking-tight text-gray-900 dark:text-white">
        Create your password
      </h1>
      <p class="mb-4 text-gray-500 dark:text-gray-400">
        Set a password for <span class="font-medium text-gray-900 dark:text-white">{{ email }}</span>.
      </p>

      <form @submit.prevent="handleSubmit">
        <div class="mb-4">
          <PasswordInput
            v-model="password"
            label="Password"
            placeholder="At least 8 characters"
            :show-strength="true"
            required
          />
        </div>

        <div class="my-4 sm:my-6">
          <Button
            type="submit"
            variant="primary"
            class="w-full"
            :disabled="isLoading || !password || password.length < 8"
          >
            {{ isLoading ? 'Creating account...' : 'Create account' }}
          </Button>
        </div>

        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400 sm:text-center md:mt-6">
          <RouterLink to="/sign-in" class="font-medium text-primary-700 hover:underline dark:text-primary-500">
            Back to Sign in
          </RouterLink>
        </p>
      </form>
    </template>
  </AuthLayout>
</template>
