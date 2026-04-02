import { computed } from 'vue'
import { useCloudMode } from './useCloudMode'

export function useFeatures() {
  const { features, loaded } = useCloudMode()

  return {
    enterpriseDashboards: computed(() => features.value.enterpriseDashboards),
    traceAuditing: computed(() => features.value.traceAuditing),
    auditLogs: computed(() => features.value.auditLogs),
    pulse: computed(() => features.value.pulse),
    featuresLoaded: loaded,
  }
}
