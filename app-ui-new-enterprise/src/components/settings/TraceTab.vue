<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import Button from '@/components/ui/Button.vue'
import DropdownFilter from '@/components/ui/datagrid/DropdownFilter.vue'
import {
  fetchInstalledApplicationsWithSyncMethods,
  type InstalledApplicationWithSyncMethods,
} from '@/services/applicationsService'
import {
  fetchTraceQuota,
  findBinding,
  removeBinding,
  setBinding,
  type PlatformServiceBinding,
  type TraceQuotaStatus,
} from '@/services/platformServicesService'
import { useToast } from '@/composables/useToast'

const SERVICE_TYPE = 'trace-ai-provider'
const REQUIRED_SYNC_METHOD = 'trace'

const { showToast } = useToast()

const loading = ref(false)
const saving = ref(false)
const removing = ref(false)
const candidates = ref<InstalledApplicationWithSyncMethods[]>([])
const binding = ref<PlatformServiceBinding | null>(null)
const selectedValue = ref<string | null>(null)

// Quota status drives the mode-aware UI:
//   - `system` (feature on, no binding): default LLM card + binding editor
//     reachable via "Connect your own LLM" toggle.
//   - `user` (feature on, binding present): "Unlimited (your own LLM)"
//     badge.
//   - `disabled` (feature flag off): tab body collapses to a short status
//     note. Note: SettingsView already hides the tab when the feature flag
//     is off, so this branch is defensive only.
const quota = ref<TraceQuotaStatus | null>(null)
const quotaError = ref<string | null>(null)

function makeOptionValue(app: { sdk: string; key: string }): string {
  return `${app.sdk}::${app.key}`
}

function parseOptionValue(value: string | null): { sdk: string; key: string } | null {
  if (!value) return null
  const idx = value.indexOf('::')
  if (idx < 0) return null
  return { sdk: value.slice(0, idx), key: value.slice(idx + 2) }
}

const candidateOptions = computed(() =>
  candidates.value
    .filter((c) => c.syncMethods.includes(REQUIRED_SYNC_METHOD))
    .sort((a, b) => a.name.localeCompare(b.name) || a.worker.localeCompare(b.worker)),
)

const dropdownOptions = computed(() =>
  candidateOptions.value.map((app) => ({
    value: makeOptionValue({ sdk: app.worker, key: app.key }),
    label: `${app.name} · ${app.worker}${app.authorized ? '' : ' (not authorized)'}`,
  })),
)

const dropdownButtonLabel = computed(() => {
  if (!selectedValue.value) return '— select an application —'
  const opt = dropdownOptions.value.find((o) => o.value === selectedValue.value)
  return opt?.label ?? '— select an application —'
})

const hasCandidates = computed(() => candidateOptions.value.length > 0)

const installedAppsDiagnostics = computed(() =>
  [...candidates.value].sort(
    (a, b) => a.worker.localeCompare(b.worker) || a.name.localeCompare(b.name),
  ),
)

const dirty = computed(() => {
  const current = binding.value
  if (!current) return Boolean(selectedValue.value)
  return (
    makeOptionValue({ sdk: current.sdk ?? '', key: current.applicationKey }) !==
    selectedValue.value
  )
})

const selectedCandidate = computed<InstalledApplicationWithSyncMethods | null>(() => {
  const parsed = parseOptionValue(selectedValue.value)
  if (!parsed) return null
  return (
    candidateOptions.value.find(
      (c) => c.key === parsed.key && c.worker === parsed.sdk,
    ) ?? null
  )
})

const selectedNotAuthorized = computed(
  () => selectedCandidate.value !== null && !selectedCandidate.value.authorized,
)

const currentBindingMissing = computed(() => {
  const current = binding.value
  if (!current) return false
  return !candidateOptions.value.some(
    (c) => c.key === current.applicationKey && c.worker === (current.sdk ?? ''),
  )
})

// `mode` is the source of truth for which surface to render. We fall back to
// the legacy "edit binding" form when the quota endpoint is unavailable
// (older instances, network blip) so users on legacy releases still get a
// functional Settings tab.
const mode = computed<'system' | 'user' | 'disabled' | 'legacy'>(() => {
  if (!quota.value) return 'legacy'
  return quota.value.mode
})

const usedFraction = computed(() => {
  if (!quota.value || quota.value.limit <= 0) return 0
  return Math.min(1, quota.value.used / quota.value.limit)
})

const usedPercent = computed(() => Math.round(usedFraction.value * 100))

const usageBarColor = computed(() => {
  if (usedFraction.value >= 1) return 'bg-red-500'
  if (usedFraction.value >= 0.8) return 'bg-amber-500'
  return 'bg-primary-600 dark:bg-primary-500'
})

// `resetAt` arrives as ISO-8601 UTC. Format in the user's local TZ so the
// banner reads "resets at 02:00 your time" without us guessing the offset.
const resetAtLocal = computed<string | null>(() => {
  const iso = quota.value?.resetAt
  if (!iso) return null
  const date = new Date(iso)
  if (Number.isNaN(date.valueOf())) return null
  return date.toLocaleString(undefined, {
    weekday: 'short',
    hour: '2-digit',
    minute: '2-digit',
  })
})

async function loadData(): Promise<void> {
  loading.value = true
  try {
    const tasks: [
      Promise<InstalledApplicationWithSyncMethods[]>,
      Promise<PlatformServiceBinding | null>,
      Promise<TraceQuotaStatus | null>,
    ] = [
      fetchInstalledApplicationsWithSyncMethods(),
      findBinding(SERVICE_TYPE),
      // Tolerate the new endpoint being unavailable (legacy backend) so the
      // tab still renders the binding editor.
      fetchTraceQuota().catch((err) => {
        quotaError.value = err instanceof Error ? err.message : String(err)
        return null
      }),
    ]

    const [installedApps, currentBinding, quotaStatus] = await Promise.all(tasks)

    candidates.value = installedApps
    binding.value = currentBinding
    quota.value = quotaStatus

    if (currentBinding && currentBinding.sdk) {
      selectedValue.value = makeOptionValue({
        sdk: currentBinding.sdk,
        key: currentBinding.applicationKey,
      })
    } else {
      selectedValue.value = null
    }
  } catch (error) {
    console.error('Failed to load Trace AI provider data:', error)
    showToast('Failed to load applications', 'error')
  } finally {
    loading.value = false
  }
}

async function handleSave(): Promise<void> {
  const parsed = parseOptionValue(selectedValue.value)
  if (!parsed) return

  saving.value = true
  try {
    binding.value = await setBinding(SERVICE_TYPE, parsed.key, parsed.sdk)
    showToast('Trace AI provider saved', 'success')
    // Refresh the quota mode now that a user binding exists.
    quota.value = await fetchTraceQuota().catch(() => null)
  } catch (error) {
    console.error('Failed to save Trace AI provider:', error)
    showToast('Failed to save Trace AI provider', 'error')
  } finally {
    saving.value = false
  }
}

async function handleClear(): Promise<void> {
  if (!binding.value) return

  removing.value = true
  try {
    await removeBinding(SERVICE_TYPE)
    binding.value = null
    selectedValue.value = null
    showToast('Trace AI provider cleared. Default LLM (system) reactivated.', 'success')
    quota.value = await fetchTraceQuota().catch(() => null)
  } catch (error) {
    console.error('Failed to clear Trace AI provider:', error)
    showToast('Failed to clear Trace AI provider', 'error')
  } finally {
    removing.value = false
  }
}

const showOverrideForm = ref(false)

onMounted(loadData)
</script>

<template>
  <div class="space-y-4">
    <div>
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Trace AI provider</h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Choose which LLM powers the Trace chat. Connect your own provider for unlimited use, or
        leave the Orchesty default in place if the included daily quota is enough.
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div
        class="h-8 w-8 animate-spin rounded-full border-4 border-gray-300 border-t-primary-600 dark:border-gray-600 dark:border-t-primary-500"
      ></div>
    </div>

    <!-- Disabled mode: feature flag is off. Defensive — SettingsView normally
         hides the tab entirely when `traceAuditing` is false. -->
    <div
      v-else-if="mode === 'disabled'"
      class="rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
    >
      Trace AI is disabled on this instance.
    </div>

    <div v-else class="space-y-4">
      <!-- System mode: Orchesty default LLM with daily cap. The badge stays
           informational — if the cloud-relay isn't reachable on this instance,
           the chat call surfaces a clear runtime error and the user can fall
           back to the binding editor below. -->
      <div
        v-if="mode === 'system' && quota"
        class="rounded-lg border border-primary-200 bg-primary-50 p-5 text-sm dark:border-primary-900 dark:bg-primary-950/40"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-sm font-semibold text-primary-900 dark:text-primary-200">
              Orchesty default LLM
            </h3>
            <p class="mt-1 text-primary-800 dark:text-primary-300">
              Trace runs against Orchesty's built-in <code class="rounded bg-primary-100 px-1 py-0.5 font-mono text-xs dark:bg-primary-900">gpt-5.4-mini</code>
              — up to {{ quota.limit }} messages per day. Connect your own LLM for unlimited use.
            </p>
          </div>
          <Button
            type="button"
            variant="outline"
            size="sm"
            @click="showOverrideForm = !showOverrideForm"
          >
            {{ showOverrideForm ? 'Cancel' : 'Connect your own LLM' }}
          </Button>
        </div>

        <div class="mt-4">
          <div class="mb-1.5 flex items-center justify-between text-xs text-primary-900 dark:text-primary-200">
            <span class="font-medium">{{ quota.used }} / {{ quota.limit }} messages used today</span>
            <span v-if="resetAtLocal">resets {{ resetAtLocal }} your time</span>
          </div>
          <div class="h-2 overflow-hidden rounded-full bg-primary-100 dark:bg-primary-900">
            <div
              :class="['h-full transition-all', usageBarColor]"
              :style="{ width: `${usedPercent}%` }"
              role="progressbar"
              :aria-valuenow="quota.used"
              :aria-valuemin="0"
              :aria-valuemax="quota.limit"
              :aria-label="`Daily Trace usage: ${quota.used} of ${quota.limit}`"
            ></div>
          </div>
        </div>
      </div>

      <!-- User mode: own LLM bound, no cap -->
      <div
        v-else-if="mode === 'user' && binding"
        class="rounded-lg border border-emerald-200 bg-emerald-50 p-5 text-sm dark:border-emerald-900 dark:bg-emerald-950/40"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">
              Your LLM is connected
              <span class="ml-1.5 inline-flex items-center rounded-full bg-emerald-200 px-2 py-0.5 text-[10px] font-semibold text-emerald-900 dark:bg-emerald-900 dark:text-emerald-200">
                Unlimited
              </span>
            </h3>
            <p class="mt-1 text-emerald-800 dark:text-emerald-300">
              Trace is calling
              <code class="rounded bg-emerald-100 px-1 py-0.5 font-mono text-xs dark:bg-emerald-900">{{ binding.applicationKey }}</code>
              on worker
              <code class="rounded bg-emerald-100 px-1 py-0.5 font-mono text-xs dark:bg-emerald-900">{{ binding.sdk ?? 'unknown' }}</code>.
              No daily limit applies.
            </p>
          </div>
          <div class="flex flex-col gap-2">
            <Button
              type="button"
              variant="outline"
              size="sm"
              @click="showOverrideForm = !showOverrideForm"
            >
              {{ showOverrideForm ? 'Cancel' : 'Change application' }}
            </Button>
            <Button
              type="button"
              variant="outline"
              size="sm"
              :loading="removing"
              @click="handleClear"
            >
              Reset to Orchesty default
            </Button>
          </div>
        </div>
      </div>

      <!-- Legacy fallback: quota endpoint unavailable. Always show editor. -->
      <div
        v-else-if="mode === 'legacy'"
        class="rounded-md border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200"
      >
        Quota status is not available on this instance. Configure the binding manually below.
      </div>

      <!-- Binding editor: visible in legacy mode, or when the user clicks
           "Connect your own LLM" / "Change application" in system / user
           mode. Default-collapsed in those two modes to keep the surface
           compact. -->
      <div
        v-if="mode === 'legacy' || showOverrideForm"
        class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800"
      >
        <!-- Empty state — no candidate apps installed -->
        <div
          v-if="!hasCandidates"
          class="space-y-3"
        >
          <div
            class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200"
          >
            <p class="font-medium">No suitable application is installed.</p>
            <p class="mt-1">
              Install and authorize an application that exposes the
              <code class="rounded bg-amber-100 px-1 py-0.5 font-mono text-xs dark:bg-amber-800">syncTrace</code>
              sync method (e.g. OpenAI or Z.AI) and come back here.
            </p>
          </div>

          <details
            class="rounded-md border border-gray-200 bg-gray-50 p-3 text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-300"
          >
            <summary class="cursor-pointer font-medium">
              Diagnostics ({{ installedAppsDiagnostics.length }} installed applications)
            </summary>
            <div v-if="installedAppsDiagnostics.length === 0" class="mt-2">
              The backend returned no installed applications.
            </div>
            <ul v-else class="mt-2 space-y-1">
              <li
                v-for="app in installedAppsDiagnostics"
                :key="`${app.worker}::${app.key}`"
                class="font-mono"
              >
                <span class="text-gray-900 dark:text-gray-100">{{ app.worker }}</span> /
                <span>{{ app.key }}</span> →
                <span v-if="app.syncMethods.length === 0" class="italic text-gray-500">
                  no sync methods
                </span>
                <span v-else>[{{ app.syncMethods.join(', ') }}]</span>
              </li>
            </ul>
          </details>
        </div>

        <form v-else class="space-y-4" @submit.prevent="handleSave">
          <!-- Stale binding warning — bound app no longer present -->
          <div
            v-if="currentBindingMissing && binding"
            class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-700 dark:bg-red-900/30 dark:text-red-200"
          >
            The current binding points to application
            <code class="font-mono">{{ binding.applicationKey }}</code>
            on worker
            <code class="font-mono">{{ binding.sdk ?? 'unknown' }}</code>,
            which is no longer installed. Pick a different application and save.
          </div>

          <div class="max-w-sm">
            <label
              id="trace-ai-provider-label"
              class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
            >
              Application
            </label>
            <DropdownFilter
              v-model="selectedValue"
              :options="dropdownOptions"
              :button-label="dropdownButtonLabel"
              dropdown-id="trace-ai-provider-dropdown"
              full-width
            />
          </div>

          <!-- Authorization warning for selected app -->
          <div
            v-if="selectedNotAuthorized"
            class="rounded-md border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200"
          >
            The selected application is not authorized. Add credentials in the Applications section,
            otherwise Trace chat will fail with an authorization error.
          </div>

          <div class="flex items-center justify-end gap-2 pt-2">
            <Button
              v-if="binding && mode !== 'system'"
              type="button"
              variant="outline"
              :loading="removing"
              :disabled="saving"
              @click="handleClear"
            >
              Clear
            </Button>
            <Button
              type="submit"
              variant="primary"
              :loading="saving"
              :disabled="!selectedValue || !dirty || removing"
            >
              Save
            </Button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
