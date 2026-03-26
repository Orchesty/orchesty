<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AuthLayout from '@/layouts/AuthLayout.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import { registerUser } from '@/services/authService'
import { invalidateUsersExistCache } from '@/router'
import { useToast } from '@/composables/useToast'

const router = useRouter()
const { showToast } = useToast()

const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const isLoading = ref(false)

const handleSubmit = async () => {
  if (isLoading.value) return

  if (password.value !== confirmPassword.value) {
    showToast('Passwords do not match', 'error')
    return
  }

  if (password.value.length < 6) {
    showToast('Password must be at least 6 characters', 'error')
    return
  }

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
      <Input
        v-model="password"
        type="password"
        label="Password"
        placeholder="••••••••"
        required
      />
      <Input
        v-model="confirmPassword"
        type="password"
        label="Confirm password"
        placeholder="••••••••"
        required
      />

      <Button type="submit" variant="primary" class="w-full !mt-8" :loading="isLoading">
        Create Account
      </Button>
    </form>
  </AuthLayout>
</template>
