<script setup lang="ts">
import { ref } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import Tabs from '@/components/ui/Tabs.vue'
import TextInput from '@/components/ui/TextInput.vue'
import Button from '@/components/ui/Button.vue'
import Card from '@/components/ui/Card.vue'
import type { Tab } from '@/components/ui/Tabs.vue'
import { useToast } from '@/composables/useToast'
import { updateProfile, updatePassword, updateNotifications } from '@/services/accountService'

// Tabs configuration
const tabs: Tab[] = [
  {
    id: 'profile',
    label: 'Profile',
    target: 'profile-content',
    icon: 'M10 0a10 10 0 1 0 10 10A10.011 10.011 0 0 0 10 0Zm0 5a3 3 0 1 1 0 6 3 3 0 0 1 0-6Zm0 13a8.949 8.949 0 0 1-4.951-1.488A3.987 3.987 0 0 1 9 13h2a3.987 3.987 0 0 1 3.951 3.512A8.949 8.949 0 0 1 10 18Z',
    iconViewBox: '0 0 20 20',
  },
  {
    id: 'notifications',
    label: 'Notifications',
    target: 'notifications-content',
    icon: 'M6.143 0H1.857A1.857 1.857 0 0 0 0 1.857v4.286C0 7.169.831 8 1.857 8h4.286A1.857 1.857 0 0 0 8 6.143V1.857A1.857 1.857 0 0 0 6.143 0Zm10 0h-4.286A1.857 1.857 0 0 0 10 1.857v4.286C10 7.169 10.831 8 11.857 8h4.286A1.857 1.857 0 0 0 18 6.143V1.857A1.857 1.857 0 0 0 16.143 0Zm-10 10H1.857A1.857 1.857 0 0 0 0 11.857v4.286C0 17.169.831 18 1.857 18h4.286A1.857 1.857 0 0 0 8 16.143v-4.286A1.857 1.857 0 0 0 6.143 10Zm10 0h-4.286A1.857 1.857 0 0 0 10 11.857v4.286c0 1.026.831 1.857 1.857 1.857h4.286A1.857 1.857 0 0 0 18 16.143v-4.286A1.857 1.857 0 0 0 16.143 10Z',
    iconViewBox: '0 0 18 18',
  },
]

// Toast notifications
const { showToast } = useToast()

// Loading states
const savingProfile = ref(false)
const savingPassword = ref(false)
const savingNotifications = ref(false)

// Profile form data
const username = ref('BonnieG')
const email = ref('bonnie@example.com')

// Password form data
const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')

// Notifications
const notifications = ref([
  {
    id: 'flowbite-comm',
    label: 'Flowbite Communication',
    description: 'Get Flowbite news, announcements, and product updates',
    enabled: false,
  },
  {
    id: 'account-activity',
    label: 'Account Activity',
    description: "Get important notifications about you or activity you've missed",
    enabled: true,
  },
  {
    id: 'push-notifications',
    label: 'Mobile push notifications',
    description: 'Receive push notifications whenever your company requires your attention',
    enabled: true,
  },
  {
    id: 'email-notification',
    label: 'Email notification',
    description: 'Receive email notifications whenever your company requires your attention',
    enabled: false,
  },
  {
    id: 'meetups',
    label: 'Meetups near me',
    description: 'Get an email when a Flowbite Meetup is posted close to my location',
    enabled: true,
  },
])

// Handlers
const handleSaveProfile = async () => {
  if (savingProfile.value) return
  
  savingProfile.value = true
  try {
    await updateProfile({ username: username.value })
    showToast('Profile updated successfully', 'success')
  } catch (error) {
    const message = error instanceof Error ? error.message : 'Failed to update profile'
    showToast(message, 'error')
  } finally {
    savingProfile.value = false
  }
}

const handleSavePassword = async () => {
  if (savingPassword.value) return
  
  // Validate passwords match
  if (newPassword.value !== confirmPassword.value) {
    showToast('Passwords do not match', 'error')
    return
  }
  
  savingPassword.value = true
  try {
    await updatePassword({
      currentPassword: currentPassword.value,
      newPassword: newPassword.value,
      confirmPassword: confirmPassword.value,
    })
    showToast('Password changed successfully', 'success')
    // Reset password fields after successful save
    currentPassword.value = ''
    newPassword.value = ''
    confirmPassword.value = ''
  } catch (error) {
    const message = error instanceof Error ? error.message : 'Failed to change password'
    showToast(message, 'error')
  } finally {
    savingPassword.value = false
  }
}

const handleSaveNotifications = async () => {
  if (savingNotifications.value) return
  
  savingNotifications.value = true
  try {
    await updateNotifications(notifications.value)
    showToast('Notification preferences saved', 'success')
  } catch (error) {
    const message = error instanceof Error ? error.message : 'Failed to save preferences'
    showToast(message, 'error')
  } finally {
    savingNotifications.value = false
  }
}

const handleSelectAll = () => {
  const allEnabled = notifications.value.every((n) => n.enabled)
  notifications.value.forEach((n) => {
    n.enabled = !allEnabled
  })
}
</script>

<template>
  <DashboardLayout>
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Account settings</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Manage your account settings and preferences
      </p>
    </div>

    <!-- Tabs -->
    <Tabs :tabs="tabs" default-tab="profile" content-id="settings-tabs-content" />

    <!-- Tab Content -->
    <div id="settings-tabs-content" class="mt-6">
      <!-- Profile Tab -->
      <div id="profile-content" role="tabpanel" aria-labelledby="profile-tab">
        <Card>
          <!-- Account Section -->
          <h2
            class="mb-4 text-xl font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-4 md:mb-6"
          >
            Account
          </h2>
          <form @submit.prevent="handleSaveProfile">
            <div class="mb-4 space-y-4 sm:mb-6 max-w-lg">
              <TextInput
                v-model="username"
                label="Username"
                placeholder="Ex. BonnieG"
                required
              />

              <div>
                <label
                  for="email"
                  class="mb-2 flex text-sm font-medium text-gray-900 dark:text-white"
                  >Your email*</label
                >
                <div class="relative">
                  <div
                    class="pointer-events-none absolute inset-y-0 start-0 top-0 flex items-center ps-3.5"
                  >
                    <svg
                      class="h-4 w-4 text-gray-500 dark:text-gray-400"
                      aria-hidden="true"
                      xmlns="http://www.w3.org/2000/svg"
                      fill="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        d="M2 5.6V18c0 1.1.9 2 2 2h16a2 2 0 0 0 2-2V5.6l-.9.7-7.9 6a2 2 0 0 1-2.4 0l-8-6-.8-.7Z"
                      ></path>
                      <path
                        d="M20.7 4.1A2 2 0 0 0 20 4H4a2 2 0 0 0-.6.1l.7.6 7.9 6 7.9-6 .8-.6Z"
                      ></path>
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

          <!-- Password Section -->
          <h2
            class="mb-4 text-xl font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-4 mt-8 md:mb-6"
          >
            Password
          </h2>
          <form @submit.prevent="handleSavePassword">
            <div class="mb-4 grid gap-4 sm:mb-6 sm:grid-cols-2">
              <div class="col-span-1 space-y-4 max-w-lg">
                <TextInput
                  v-model="currentPassword"
                  type="password"
                  label="Current password"
                  placeholder="Enter your current password"
                  required
                />

                <TextInput
                  v-model="newPassword"
                  type="password"
                  label="Your new password"
                  placeholder="Enter your new password"
                  required
                />

                <TextInput
                  v-model="confirmPassword"
                  type="password"
                  label="Confirm new password"
                  placeholder="Confirm new password"
                  required
                />
              </div>
            </div>
            <Button type="submit" :disabled="savingPassword">
              {{ savingPassword ? 'Saving...' : 'Save changes' }}
            </Button>
          </form>
        </Card>
      </div>

      <!-- Notifications Tab -->
      <div id="notifications-content" role="tabpanel" aria-labelledby="notifications-tab" class="hidden">
        <Card>
          <div
            class="flex items-center justify-between mb-4 md:mb-6 border-b border-gray-200 dark:border-gray-700 pb-4"
          >
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Notifications</h2>
            <button
              type="button"
              @click="handleSelectAll"
              class="text-sm font-medium text-primary-700 hover:underline dark:text-primary-500"
            >
              Select all
            </button>
          </div>

          <div class="mb-4 sm:mb-6">
            <label
              v-for="notification in notifications"
              :key="notification.id"
              class="relative mb-4 flex cursor-pointer"
            >
              <input
                v-model="notification.enabled"
                type="checkbox"
                class="peer sr-only"
              />
              <div
                class="peer h-6 w-11 shrink-0 rounded-full bg-gray-200 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-primary-800 rtl:peer-checked:after:-translate-x-full"
              ></div>
              <div class="ms-3">
                <span class="font-medium text-gray-900 dark:text-gray-300">{{
                  notification.label
                }}</span>
                <p class="text-sm font-normal text-gray-500 dark:text-gray-300">
                  {{ notification.description }}
                </p>
              </div>
            </label>
          </div>

          <Button @click="handleSaveNotifications" :disabled="savingNotifications">
            {{ savingNotifications ? 'Saving...' : 'Save changes' }}
          </Button>
        </Card>
      </div>
    </div>
  </DashboardLayout>
</template>

