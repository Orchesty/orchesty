<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import AuthLayout from '@/layouts/AuthLayout.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import PasswordInput from '@/components/ui/PasswordInput.vue'
import { registerUser } from '@/services/authService'
import { invalidateUsersExistCache } from '@/router'
import { useToast } from '@/composables/useToast'

const router = useRouter()
const { showToast } = useToast()

const email = ref('')
const password = ref('')
const isLoading = ref(false)

const isPasswordStrong = computed(() => {
  const p = password.value
  if (p.length < 8) return false
  const types = [/[a-z]/, /[A-Z]/, /\d/, /[^a-zA-Z0-9]/].filter(r => r.test(p)).length
  return types >= 3
})

const handleSubmit = async () => {
  if (isLoading.value) return

  isLoading.value = true

  try {
    await registerUser(email.value, password.value)
    invalidateUsersExistCache()
    showToast('Account created successfully. Please sign in.', 'success')
    router.push('/sign-in')
  } catch (error) {
    console.error('Registration error:', error)
    const errorMessage = error instanceof Error ? error.message : 'Failed to create account. Please try again.'
    showToast(errorMessage, 'error')
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <h1 class="mb-2 text-2xl text-center font-extrabold leading-tight tracking-tight text-gray-900 dark:text-white">
      Set up your instance
    </h1>
    <p class="text-sm text-center text-gray-500 dark:text-gray-400">
      Create your administrator account to get started.
    </p>

    <form class="mt-6 space-y-4" @submit.prevent="handleSubmit">
      <Input
        v-model="email"
        type="email"
        label="Email"
        placeholder="admin@company.com"
        required
      />
      <PasswordInput
        v-model="password"
        label="Password"
        placeholder="At least 8 characters"
        :show-strength="true"
        required
      />

      <Button type="submit" variant="primary" class="w-full !mt-8" :loading="isLoading" :disabled="!isPasswordStrong">
        Create Account
      </Button>
    </form>

    <p class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
      Already have an account?
      <router-link to="/sign-in" class="font-medium text-primary-700 hover:underline dark:text-primary-500">Sign in</router-link>
    </p>
  </AuthLayout>
</template>
