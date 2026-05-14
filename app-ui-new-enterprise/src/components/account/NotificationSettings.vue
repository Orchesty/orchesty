<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import Card from '@/components/ui/Card.vue'
import LoadingSpinner from '@/components/ui/LoadingSpinner.vue'
import type { NotificationPreset, NotificationSubscription } from '@/types/account'
import { useToast } from '@/composables/useToast'
import { useCloudMode } from '@/composables/useCloudMode'
import { fetchSubscriptions, upsertSubscription } from '@/services/notificationService'

// Local extension of `NotificationPreset` adds three pieces of metadata that
// drive rendering only — the wire payload sent to the notifier still uses the
// shared `NotificationSubscription` shape.
//
// `group` lets us bucket presets under a section heading.
// `cloudOnly` hides presets whose backend producer (e.g. `cloud_limit_threshold`
// is published only by `CloudLimitsTickCommand`, which runs in cloud-enabled
// deployments) so an on-prem user cannot subscribe to an event that will never
// fire on their instance.
// `defaultEnabled` mirrors the notifier's `Preset.DefaultSubscribed` flag (see
// `notifier/pkg/service/preset.go`). The notifier itself treats users without
// any explicit row on a default-subscribed preset as implicit recipients, so
// the email *will* be sent regardless of UI state. We mirror the flag here so
// the UI shows the toggle as ON for that implicit state — otherwise a
// brand-new user would see an empty list of toggles and assume nothing is
// active. When the user explicitly clicks the toggle, an explicit
// Subscription row is upserted (enabled or disabled) and from that point on
// `existing.enabled` wins over `defaultEnabled`.
type LocalPreset = NotificationPreset & {
  group: 'topology' | 'limits'
  cloudOnly?: boolean
  defaultEnabled?: boolean
}

// Notifier supports more presets (topology_failed, topology_failed_repeatedly,
// topology_slow, limit_recovered) but we intentionally do NOT expose them in
// the UI:
//   - topology failures are already covered by `topology_failed_message` (a
//     run that produces a trash entry always reports `topology_failed` too,
//     so a separate toggle would be redundant noise).
//   - `topology_slow` cannot have a sensible global threshold; users would
//     need per-topology configuration which we do not offer yet.
//   - `limit_recovered` is purely informational and not worth an email.
// Without a UI toggle these subscriptions are never created, so the notifier
// resolves zero recipients and skips the email dispatch entirely.
const PRESETS: LocalPreset[] = [
  {
    id: 'topology_failed_message',
    group: 'topology',
    defaultEnabled: true,
    label: 'Failed Message (Trash)',
    description: 'Notify when a message is moved to trash',
  },
  {
    id: 'limit_overflow',
    group: 'limits',
    defaultEnabled: true,
    label: 'Resource limit overflow',
    description: 'Notify when an instance resource limit is exceeded and messages are being dropped',
  },
  {
    id: 'cloud_limit_threshold',
    group: 'limits',
    cloudOnly: true,
    defaultEnabled: true,
    label: 'Plan limit threshold',
    description: 'Notify when an instance plan resource (messages or storage) approaches or crosses its limit (80%, 90%, 100%)',
  },
]

const { showToast } = useToast()
const { cloudMode } = useCloudMode()

const loading = ref(false)
const savingPreset = ref<string | null>(null)
const subscriptionState = reactive<Record<string, boolean>>({})
const subscriptionsLoaded = ref(false)

const visiblePresets = computed(() =>
  PRESETS.filter((preset) => !preset.cloudOnly || cloudMode.value),
)

// Drives the template — sections render only when they contain at least one
// visible preset, which keeps the heading from showing alone (e.g. on-prem
// would otherwise see an empty "Resource limits" header if `cloud_limit_threshold`
// were the only entry in that group).
const groups = computed<Array<{ title: string; presets: LocalPreset[] }>>(() => [
  {
    title: 'Topology incidents',
    presets: visiblePresets.value.filter((preset) => preset.group === 'topology'),
  },
  {
    title: 'Resource limits',
    presets: visiblePresets.value.filter((preset) => preset.group === 'limits'),
  },
])

function mergeSubscriptions(subs: NotificationSubscription[]) {
  // Walk the full PRESETS list (not just `visiblePresets`) so the state for a
  // hidden-but-already-subscribed preset is still captured. The user cannot
  // toggle it in this UI, but other UIs / direct API calls can — keeping the
  // state consistent prevents accidental clobbering on the next upsert.
  for (const preset of PRESETS) {
    const existing = subs.find(
      (s) => (s.event_type || s.subject_id) === preset.id && (s.channel || 'email') === 'email',
    )
    // Fallback chain: explicit row > default-subscribed flag > off. The
    // default fallback only fires when there is *no* row in the notifier
    // store, so once the user toggles a default preset off it stays off
    // (`existing.enabled === false` wins over `defaultEnabled`).
    subscriptionState[preset.id] = existing?.enabled ?? !!preset.defaultEnabled
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
    // On load failure we still want default-subscribed presets to read as ON,
    // matching the notifier's actual recipient resolution. The user keeps
    // the ability to toggle off — the toggle handler upserts via the API
    // independently of the (failed) initial load.
    for (const preset of PRESETS) {
      subscriptionState[preset.id] = !!preset.defaultEnabled
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

    <div v-else-if="subscriptionsLoaded" class="space-y-8">
      <template v-for="group in groups" :key="group.title">
        <section v-if="group.presets.length">
          <h3
            class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
          >
            {{ group.title }}
          </h3>
          <div class="space-y-3">
            <div
              v-for="preset in group.presets"
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
        </section>
      </template>
    </div>
  </Card>
</template>
