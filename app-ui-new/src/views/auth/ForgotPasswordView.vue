<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AuthLayout from '@/layouts/AuthLayout.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import { resetPassword } from '@/services/authService'
import { useToast } from '@/composables/useToast'

const router = useRouter()
const { showToast } = useToast()

const email = ref('')
const isLoading = ref(false)

const handleSubmit = async () => {
  if (isLoading.value) return

  isLoading.value = true
  try {
    await resetPassword(email.value)
    showToast('Reset email sent. Check your inbox for instructions.', 'success')
  } catch (error) {
    console.error('Password reset error:', error)
    const errorMessage = error instanceof Error ? error.message : 'Failed to send reset email. Please try again.'
    showToast(errorMessage, 'error')
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <h1 class="mb-2 text-2xl font-extrabold leading-tight tracking-tight text-gray-900 dark:text-white">
      Reset your password
    </h1>
    <p class="text-gray-500 dark:text-gray-400">
      We'll email you instructions to reset your password.
    </p>

    <form @submit.prevent="handleSubmit">
      <div class="my-4 mb-8 sm:my-6">
        <Input
          v-model="email"
          type="email"
          label="Email address"
          placeholder="john.doe@company.com"
          required
        />
      </div>

      <div class="mb-4 mt-12">
        <Button type="submit" variant="primary" class="w-full" :disabled="isLoading">
          {{ isLoading ? 'Sending...' : 'Send confirmation email' }}
        </Button>
      </div>
    </form>
  </AuthLayout>
</template>

