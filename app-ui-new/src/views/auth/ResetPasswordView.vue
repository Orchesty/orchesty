<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AuthLayout from '@/layouts/AuthLayout.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'

const router = useRouter()
const password = ref('')
const confirmPassword = ref('')

const handleSubmit = () => {
  if (password.value !== confirmPassword.value) {
    console.error('Passwords do not match')
    return
  }
  console.log('Reset password:', password.value)
  // TODO: Implement password reset logic
  // router.push('/sign-in')
}
</script>

<template>
  <AuthLayout>
    <h1 class="mb-2 text-2xl font-extrabold leading-tight tracking-tight text-gray-900 dark:text-white">
      Create new password
    </h1>
    <p class="mb-4 text-gray-500 dark:text-gray-400">
      Your new password must be different from previous used passwords.
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
        <Button type="submit" variant="primary" class="w-full">
          Confirm new password
        </Button>
      </div>

      <p class="mt-4 text-sm text-gray-500 dark:text-gray-400 sm:text-center md:mt-6">
        <RouterLink to="/sign-in" class="font-medium text-primary-700 hover:underline dark:text-primary-500">
          Back to Sign in
        </RouterLink>
      </p>
    </form>
  </AuthLayout>
</template>

