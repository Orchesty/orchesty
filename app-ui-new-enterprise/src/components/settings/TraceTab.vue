<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import Button from '@/components/ui/Button.vue'
import DropdownFilter from '@/components/ui/datagrid/DropdownFilter.vue'
import {
  fetchInstalledApplicationsWithSyncMethods,
  type InstalledApplicationWithSyncMethods,
} from '@/services/applicationsService'
import {
  findBinding,
  removeBinding,
  setBinding,
  type PlatformServiceBinding,
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
    label: `${app.name} · ${app.worker}${app.authorized ? '' : ' (neautorizovaná)'}`,
  })),
)

const dropdownButtonLabel = computed(() => {
  if (!selectedValue.value) return '— vyber aplikaci —'
  const opt = dropdownOptions.value.find((o) => o.value === selectedValue.value)
  return opt?.label ?? '— vyber aplikaci —'
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

async function loadData(): Promise<void> {
  loading.value = true
  try {
    const [installedApps, currentBinding] = await Promise.all([
      fetchInstalledApplicationsWithSyncMethods(),
      findBinding(SERVICE_TYPE),
    ])

    candidates.value = installedApps
    binding.value = currentBinding

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
    showToast('Trace AI provider cleared', 'success')
  } catch (error) {
    console.error('Failed to clear Trace AI provider:', error)
    showToast('Failed to clear Trace AI provider', 'error')
  } finally {
    removing.value = false
  }
}

onMounted(loadData)
</script>

<template>
  <div class="space-y-4">
    <div>
      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Trace AI provider</h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Vyber nainstalovanou aplikaci, která bude obsluhovat AI dotazy z chatu na stránce Trace.
        Nabízí se pouze aplikace, jejichž implementace zveřejňuje sync metodu
        <code class="rounded bg-gray-100 px-1 py-0.5 text-xs font-mono dark:bg-gray-700">{{ REQUIRED_SYNC_METHOD }}</code>.
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div
        class="h-8 w-8 animate-spin rounded-full border-4 border-gray-300 border-t-primary-600 dark:border-gray-600 dark:border-t-primary-500"
      ></div>
    </div>

    <div v-else class="rounded-lg bg-white p-6 shadow-sm dark:bg-gray-800">
      <!-- Empty state — no candidate apps installed -->
      <div
        v-if="!hasCandidates"
        class="space-y-3"
      >
        <div
          class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200"
        >
          <p class="font-medium">Žádná vhodná aplikace není nainstalovaná.</p>
          <p class="mt-1">
            Nainstaluj a autorizuj aplikaci, která implementuje sync metodu
            <code class="rounded bg-amber-100 px-1 py-0.5 font-mono text-xs dark:bg-amber-800">syncTrace</code>
            (např. OpenAI nebo Z.AI), a vrať se sem.
          </p>
        </div>

        <details
          class="rounded-md border border-gray-200 bg-gray-50 p-3 text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-300"
        >
          <summary class="cursor-pointer font-medium">
            Diagnostika ({{ installedAppsDiagnostics.length }} nainstalovaných aplikací)
          </summary>
          <div v-if="installedAppsDiagnostics.length === 0" class="mt-2">
            Backend nevrátil žádné nainstalované aplikace.
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
                bez sync metod
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
          Aktuální nastavení odkazuje na aplikaci
          <code class="font-mono">{{ binding.applicationKey }}</code>
          na workeru
          <code class="font-mono">{{ binding.sdk ?? 'unknown' }}</code>,
          která už není nainstalovaná. Zvol jinou aplikaci a ulož.
        </div>

        <div class="max-w-sm">
          <label
            id="trace-ai-provider-label"
            class="mb-2 block text-sm font-medium text-gray-900 dark:text-white"
          >
            Aplikace
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
          Vybraná aplikace není autorizovaná. Doplň přihlašovací údaje v sekci Applications,
          jinak Trace chat selže s chybou autorizace.
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
          <Button
            v-if="binding"
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
</template>
