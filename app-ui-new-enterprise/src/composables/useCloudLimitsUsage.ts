import { computed, onScopeDispose, ref } from 'vue'
import {
  fetchLimitsUsage,
  type CloudLimitBand,
  type CloudLimitsUsage,
} from '@/services/cloudLimitsService'

const POLL_MS = 60_000

const usage = ref<CloudLimitsUsage | null>(null)
const error = ref<string | null>(null)
const lastUpdated = ref<Date | null>(null)
const loading = ref(false)

let timer: ReturnType<typeof setInterval> | null = null
let inFlight: Promise<void> | null = null
let consumers = 0

async function refresh(): Promise<void> {
  if (inFlight) return inFlight
  loading.value = true
  inFlight = (async () => {
    try {
      const data = await fetchLimitsUsage()
      usage.value = data
      lastUpdated.value = new Date()
      error.value = null
    } catch (e) {
      error.value = e instanceof Error ? e.message : String(e)
    } finally {
      loading.value = false
      inFlight = null
    }
  })()
  return inFlight
}

function start(): void {
  if (timer) return
  void refresh()
  timer = setInterval(() => {
    void refresh()
  }, POLL_MS)
}

function stop(): void {
  if (timer) {
    clearInterval(timer)
    timer = null
  }
}

const bands = computed(() => ({
  messages: usage.value?.band?.messages ?? ('none' as CloudLimitBand),
  storage: usage.value?.band?.storage ?? ('none' as CloudLimitBand),
}))

const highestBand = computed<CloudLimitBand>(() => {
  const order: CloudLimitBand[] = ['none', 'warning', 'critical', 'exceeded']
  let highest: CloudLimitBand = 'none'
  for (const b of [bands.value.messages, bands.value.storage]) {
    if (order.indexOf(b) > order.indexOf(highest)) highest = b
  }
  return highest
})

export function useCloudLimitsUsage() {
  consumers += 1
  start()

  onScopeDispose(() => {
    consumers -= 1
    if (consumers <= 0) {
      consumers = 0
      stop()
    }
  })

  return {
    usage,
    bands,
    highestBand,
    lastUpdated,
    loading,
    error,
    refresh,
  }
}
