<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import Card from '@/components/ui/Card.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import type { NotificationPreset, NotificationSubscription } from '@/types/account'
import { useToast } from '@/composables/useToast'
import { fetchSubscriptions, upsertSubscription } from '@/services/notificationService'

const PRESETS: NotificationPreset[] = [
  {
    id: 'topology_failed_message',
    label: 'Failed Message (Trash)',
    description: 'Notify when a message is moved to trash',
  },
]

const { showToast } = useToast()

const loading = ref(false)
const savingPreset = ref<string | null>(null)
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
  loading.value = true
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
    loading.value = false
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

onMounted(loadSubscriptions)
</script>

<template>
  <Card>
    <div class="mb-4 md:mb-6 border-b border-gray-200 dark:border-gray-700 pb-4">
      <h2 class="text-xl font-bold text-gray-900 dark:text-white">Email Notifications</h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Choose which events trigger an email notification. Changes are saved immediately.
      </p>
    </div>

    <LoadingSpinner v-if="loading" message="Loading notification preferences…" />

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
</template>
