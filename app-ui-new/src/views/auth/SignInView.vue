<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AuthLayout from '@/layouts/AuthLayout.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'
import { useAuthStore } from '@/stores/auth'
import { useToast } from '@/composables/useToast'

const router = useRouter()
const authStore = useAuthStore()
const { showToast } = useToast()

const email = ref('')
const password = ref('')
const isLoading = ref(false)

const handleSubmit = async () => {
  if (isLoading.value) return

  isLoading.value = true

  try {
    await authStore.login(email.value, password.value)
    showToast('Welcome back!', 'success')
    router.push('/dashboard')
  } catch (error) {
    console.error('Login error:', error)
    const errorMessage = error instanceof Error ? error.message : 'Invalid credentials. Please try again.'
    showToast(errorMessage, 'error')
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <h1 class="mb-2 text-2xl text-center font-bold leading-tight tracking-tight text-gray-900 dark:text-white">
      Sign in to your instance
    </h1>
    
    <form class="mt-6 space-y-4" @submit.prevent="handleSubmit">
      <Input
        v-model="email"
        type="email"
        label="Email"
        placeholder="name@company.com"
        required
      />
      <Input
        v-model="password"
        type="password"
        label="Password"
        placeholder="••••••••"
        required
      />

      <Button type="submit" variant="primary" class="w-full !mt-8" :loading="isLoading">
        Sign in
      </Button>
    </form>
  </AuthLayout>
</template>
