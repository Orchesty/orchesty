<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import AuthLayout from '@/layouts/AuthLayout.vue'
import Button from '@/components/ui/Button.vue'
import Input from '@/components/ui/Input.vue'

const router = useRouter()
const email = ref('')

const handleSubmit = () => {
  console.log('Send reset email to:', email.value)
  // TODO: Implement password reset logic
  // router.push('/reset-password')
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
        <Button type="submit" variant="primary" class="w-full">
          Send confirmation email
        </Button>
      </div>
    </form>
  </AuthLayout>
</template>

