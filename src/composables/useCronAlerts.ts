import { ref } from 'vue'
import { checkMisconfiguredCrons } from '@/services/scheduledTasksService'

const hasMisconfiguredCrons = ref(false)

export function useCronAlerts() {
  const refresh = async () => {
    try {
      hasMisconfiguredCrons.value = await checkMisconfiguredCrons()
    } catch {
      hasMisconfiguredCrons.value = false
    }
  }

  return {
    hasMisconfiguredCrons,
    refresh,
  }
}
