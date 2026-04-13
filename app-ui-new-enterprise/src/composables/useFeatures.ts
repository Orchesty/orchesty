import { computed } from 'vue'
import { useCloudMode } from './useCloudMode'

export function useFeatures() {
  const { features, limits, loaded } = useCloudMode()

  return {
    enterpriseDashboards: computed(() => features.value.enterpriseDashboards),
    traceAuditing: computed(() => features.value.traceAuditing),
    auditLogs: computed(() => features.value.auditLogs),
    pulse: computed(() => features.value.pulse),
    featuresLoaded: loaded,
  }
}

export function useLimits() {
  const { limits, loaded } = useCloudMode()

  return {
    topologySlots: computed(() => limits.value.topologySlots),
    messages: computed(() => limits.value.messages),
    storageGb: computed(() => limits.value.storageGb),
    limitsLoaded: loaded,
  }
}
