<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AuthLayout from '@/layouts/AuthLayout.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import { verifyResetToken, setNewPassword } from '@/services/authService'
import { useToast } from '@/composables/useToast'

interface Props {
  token: string
}

const props = defineProps<Props>()

const router = useRouter()
const { showToast } = useToast()

const password = ref('')
const confirmPassword = ref('')
const email = ref('')
const verifying = ref(true)
const isLoading = ref(false)

// Verify token on mount
onMounted(async () => {
  try {
    const result = await verifyResetToken(props.token)
    email.value = result.email
    verifying.value = false
  } catch (error) {
    console.error('Invalid or expired reset token:', error)
    showToast('Invalid or expired reset link. Please request a new one.', 'error')
    router.push('/sign-in')
  }
})

const handleSubmit = async () => {
  if (isLoading.value) return

  if (password.value !== confirmPassword.value) {
    showToast('Passwords do not match.', 'error')
    return
  }

  isLoading.value = true
  try {
    await setNewPassword(props.token, password.value)
    showToast('Password has been reset successfully.', 'success')
    router.push('/sign-in')
  } catch (error) {
    console.error('Set password error:', error)
    const errorMessage = error instanceof Error ? error.message : 'Failed to set new password. Please try again.'
    showToast(errorMessage, 'error')
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <!-- Loading state while verifying token -->
    <div v-if="verifying" class="flex justify-center py-12">
      <LoadingSpinner message="Verifying reset link..." />
    </div>

    <!-- Form shown after successful verification -->
    <template v-else>
      <h1 class="mb-2 text-2xl font-extrabold leading-tight tracking-tight text-gray-900 dark:text-white">
        Create new password
      </h1>
      <p class="mb-4 text-gray-500 dark:text-gray-400">
        Set a new password for <span class="font-medium text-gray-900 dark:text-white">{{ email }}</span>.
      </p>

      <form @submit.prevent="handleSubmit">
        <div class="mb-4">
          <Input
            v-model="password"
            type="password"
            label="Password"
            placeholder="•••••••••"
            required
          />
        </div>

        <div class="mb-4">
          <Input
            v-model="confirmPassword"
            type="password"
            label="Confirm password"
            placeholder="•••••••••"
            required
          />
        </div>

        <div class="my-4 sm:my-6">
          <Button type="submit" variant="primary" class="w-full" :disabled="isLoading">
            {{ isLoading ? 'Saving...' : 'Confirm new password' }}
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

