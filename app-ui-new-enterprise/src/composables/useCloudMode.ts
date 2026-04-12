import { ref, readonly } from 'vue'
import { BACKEND_URL } from '@/config'

export interface Features {
  enterpriseDashboards: boolean
  traceAuditing: boolean
  auditLogs: boolean
  pulse: boolean
}

const cloudMode = ref(false)
const cloudUrl = ref('')
const instanceName = ref('')
const loaded = ref(false)
const systemWorkerNames = ref<string[]>([])

const features = ref<Features>({
  enterpriseDashboards: true,
  traceAuditing: true,
  auditLogs: true,
  pulse: true,
})

export function useCloudMode() {
  async function loadCloudMode() {
    if (loaded.value) return

    try {
      const res = await fetch(`${BACKEND_URL}/api/status`, {
        headers: { 'Accept': 'application/json' },
      })
      if (res.ok) {
        const data = await res.json()
        cloudMode.value = data.cloudMode === true
        cloudUrl.value = data.cloudUrl || ''
        instanceName.value = data.instanceName || ''

        if (data.features) {
          features.value = {
            enterpriseDashboards: data.features.enterpriseDashboards === true,
            traceAuditing: data.features.traceAuditing === true,
            auditLogs: data.features.auditLogs === true,
            pulse: data.features.pulse === true,
          }
        } else if (!cloudMode.value) {
          features.value = {
            enterpriseDashboards: true,
            traceAuditing: true,
            auditLogs: true,
            pulse: true,
          }
        }

        systemWorkerNames.value = Array.isArray(data.systemWorkerNames) ? data.systemWorkerNames : []
      }
    } catch {
      cloudMode.value = false
      cloudUrl.value = ''
      instanceName.value = ''
      features.value = {
        enterpriseDashboards: true,
        traceAuditing: true,
        auditLogs: true,
        pulse: true,
      }
    }

    loaded.value = true
  }

  return {
    cloudMode: readonly(cloudMode),
    cloudUrl: readonly(cloudUrl),
    instanceName: readonly(instanceName),
    features: readonly(features),
    systemWorkerNames: readonly(systemWorkerNames),
    loaded: readonly(loaded),
    loadCloudMode,
  }
}
