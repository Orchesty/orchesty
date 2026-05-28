<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import TextInput from '@/components/ui/TextInput.vue'
import PasswordInput from '@/components/ui/PasswordInput.vue'
import Button from '@/components/ui/Button.vue'
import Card from '@/components/ui/Card.vue'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import { updateProfile, updatePassword } from '@/services/accountService'

const { showToast } = useToast()
const authStore = useAuthStore()

const savingProfile = ref(false)
const savingPassword = ref(false)

const username = ref('')
const email = ref('')

const currentPassword = ref('')
const newPassword = ref('')

const isPasswordFormValid = computed(() =>
  currentPassword.value.length > 0 && newPassword.value.length >= 8,
)

onMounted(() => {
  if (authStore.user) {
    email.value = authStore.user.email
    username.value = authStore.user.settings?.username || ''
  }
})

const handleSaveProfile = async () => {
  if (savingProfile.value || !authStore.user) return

  savingProfile.value = true
  try {
    const mergedSettings = { ...authStore.user.settings, username: username.value }
    await updateProfile(authStore.user.id, mergedSettings)
    authStore.user.settings = mergedSettings
    localStorage.setItem('auth_user', JSON.stringify(authStore.user))
    showToast('Profile updated successfully', 'success')
  } catch (error) {
    const message = error instanceof Error ? error.message : 'Failed to update profile'
    showToast(message, 'error')
  } finally {
    savingProfile.value = false
  }
}

const handleSavePassword = async () => {
  if (savingPassword.value || !isPasswordFormValid.value) return

  savingPassword.value = true
  try {
    await updatePassword({
      currentPassword: currentPassword.value,
      newPassword: newPassword.value,
    })
    showToast('Password changed successfully', 'success')
    currentPassword.value = ''
    newPassword.value = ''
  } catch (error) {
    const message = error instanceof Error ? error.message : 'Failed to change password'
    showToast(message, 'error')
  } finally {
    savingPassword.value = false
  }
}
</script>

<template>
  <main class="h-full overflow-y-auto">
    <div class="px-4 pb-4 pt-6">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Account settings</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Manage your account settings
      </p>
    </div>

    <Card>
      <h2 class="mb-4 text-xl font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-4 md:mb-6">
        Account
      </h2>
      <form @submit.prevent="handleSaveProfile">
        <div class="mb-4 space-y-4 sm:mb-6 max-w-lg">
          <TextInput v-model="username" label="Username" placeholder="Ex. BonnieG" required />

          <div>
            <label for="email" class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white">Your email*</label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 start-0 top-0 flex items-center ps-3.5">
                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M2 5.6V18c0 1.1.9 2 2 2h16a2 2 0 0 0 2-2V5.6l-.9.7-7.9 6a2 2 0 0 1-2.4 0l-8-6-.8-.7Z"></path>
                  <path d="M20.7 4.1A2 2 0 0 0 20 4H4a2 2 0 0 0-.6.1l.7.6 7.9 6 7.9-6 .8-.6Z"></path>
                </svg>
              </div>
              <input
                v-model="email"
                type="email"
                id="email"
                class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 ps-10 text-sm text-gray-900 focus:border-primary-600 focus:ring-primary-600 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:placeholder-gray-400 dark:focus:border-primary-500 dark:focus:ring-primary-500 cursor-not-allowed"
                placeholder="name@example.com"
                disabled
                readonly
              />
            </div>
          </div>
        </div>

        <Button type="submit" :disabled="savingProfile">
          {{ savingProfile ? 'Saving...' : 'Save changes' }}
        </Button>
      </form>

      <h2 class="mb-4 text-xl font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-4 mt-8 md:mb-6">
        Password
      </h2>
      <form @submit.prevent="handleSavePassword">
        <div class="mb-4 space-y-4 sm:mb-6 max-w-lg">
          <PasswordInput v-model="currentPassword" label="Current password" placeholder="Enter your current password" required />
          <PasswordInput v-model="newPassword" label="New password" placeholder="Enter your new password" show-strength required />
        </div>
        <Button type="submit" :disabled="savingPassword || !isPasswordFormValid">
          {{ savingPassword ? 'Saving...' : 'Save changes' }}
        </Button>
      </form>
    </Card>
    </div>
  </main>
</template>
