<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import Tabs from '@/components/ui/Tabs.vue'
import TextInput from '@/components/ui/TextInput.vue'
import PasswordInput from '@/components/ui/PasswordInput.vue'
import Button from '@/components/ui/Button.vue'
import Card from '@/components/ui/Card.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import type { Tab } from '@/components/ui/Tabs.vue'
import type { NotificationPreset, NotificationSubscription } from '@/types/account'
import { useToast } from '@/composables/useToast'
import { useAuthStore } from '@/stores/auth'
import { STORAGE_KEYS } from '@/config'
import { updateProfile, updatePassword } from '@/services/accountService'
import { fetchSubscriptions, upsertSubscription } from '@/services/notificationService'

const PRESETS: NotificationPreset[] = [
  {
    id: 'topology_failed_message',
    label: 'Failed Message (Trash)',
    description: 'Notify when a message is moved to trash',
  },
]

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

const { showToast } = useToast()
const authStore = useAuthStore()

const savingProfile = ref(false)
const savingPassword = ref(false)
const loadingNotifications = ref(false)
const savingPreset = ref<string | null>(null)

const username = ref('')
const email = ref('')

const currentPassword = ref('')
const newPassword = ref('')

const isPasswordFormValid = computed(() =>
  currentPassword.value.length > 0 && newPassword.value.length >= 8,
)

const subscriptionState = reactive<Record<string, boolean>>({})
const subscriptionsLoaded = ref(false)

function mergeSubscriptions(subs: NotificationSubscription[]) {
  for (const preset of PRESETS) {
    const existing = subs.find(
      (s) => (s.event_type || s.subject_id) === preset.id && (s.channel || 'email') === 'email',
    )
    subscriptionState[preset.id] = existing?.enabled ?? false
  }
}

async function loadSubscriptions() {
  loadingNotifications.value = true
  try {
    const subs = await fetchSubscriptions()
    mergeSubscriptions(subs)
    subscriptionsLoaded.value = true
  } catch (error) {
    console.error('Failed to load notification subscriptions:', error)
    for (const preset of PRESETS) {
      subscriptionState[preset.id] = false
    }
    subscriptionsLoaded.value = true
  } finally {
    loadingNotifications.value = false
  }
}

async function handleTogglePreset(presetId: string) {
  const newEnabled = !subscriptionState[presetId]
  savingPreset.value = presetId
  try {
    const subs = await upsertSubscription({
      event_type: presetId,
      channel: 'email',
      enabled: newEnabled,
    })
    mergeSubscriptions(subs)
    showToast(
      newEnabled ? 'Notification enabled' : 'Notification disabled',
      'success',
    )
  } catch (error) {
    const message = error instanceof Error ? error.message : 'Failed to update notification'
    showToast(message, 'error')
  } finally {
    savingPreset.value = null
  }
}

onMounted(async () => {
  if (authStore.user) {
    email.value = authStore.user.email
    username.value = authStore.user.settings?.username || ''
  }
  await loadSubscriptions()
})

const handleSaveProfile = async () => {
  if (savingProfile.value || !authStore.user) return
  
  savingProfile.value = true
  try {
    const mergedSettings = { ...authStore.user.settings, username: username.value }
    await updateProfile(authStore.user.id, mergedSettings)
    authStore.user.settings = mergedSettings
    localStorage.setItem(STORAGE_KEYS.AUTH_USER, JSON.stringify(authStore.user))
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
  <main class="h-full overflow-y-auto"><div class="px-4 pb-4 pt-6">
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
            <div class="mb-4 space-y-4 sm:mb-6 max-w-lg">
              <PasswordInput
                v-model="currentPassword"
                label="Current password"
                placeholder="Enter your current password"
                required
              />

              <PasswordInput
                v-model="newPassword"
                label="New password"
                placeholder="Enter your new password"
                show-strength
                required
              />
            </div>
            <Button type="submit" :disabled="savingPassword || !isPasswordFormValid">
              {{ savingPassword ? 'Saving...' : 'Save changes' }}
            </Button>
          </form>
        </Card>
      </div>

      <!-- Notifications Tab -->
      <div id="notifications-content" role="tabpanel" aria-labelledby="notifications-tab" class="hidden">
        <Card>
          <div
            class="mb-4 md:mb-6 border-b border-gray-200 dark:border-gray-700 pb-4"
          >
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Email Notifications</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Choose which events trigger an email notification. Changes are saved immediately.
            </p>
          </div>

          <LoadingSpinner v-if="loadingNotifications" message="Loading notification preferences…" />

          <div v-else-if="subscriptionsLoaded" class="space-y-6">
            <div
              v-for="preset in PRESETS"
              :key="preset.id"
              class="rounded-lg border border-gray-200 p-4 dark:border-gray-700"
            >
              <label class="relative flex cursor-pointer items-start">
                <input
                  type="checkbox"
                  class="peer sr-only"
                  :checked="subscriptionState[preset.id]"
                  :disabled="savingPreset === preset.id"
                  @change="handleTogglePreset(preset.id)"
                />
                <div
                  class="peer h-6 w-11 shrink-0 rounded-full bg-gray-200 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-hidden peer-focus:ring-4 peer-focus:ring-primary-300 peer-disabled:opacity-50 dark:border-gray-600 dark:bg-gray-700 dark:peer-focus:ring-primary-800 rtl:peer-checked:after:-translate-x-full"
                ></div>
                <div class="ms-3">
                  <span class="font-medium text-gray-900 dark:text-gray-300">{{ preset.label }}</span>
                  <p class="text-sm font-normal text-gray-500 dark:text-gray-400">
                    {{ preset.description }}
                  </p>
                </div>
              </label>
            </div>
          </div>
        </Card>
      </div>
    </div>
  </div></main>
</template>

