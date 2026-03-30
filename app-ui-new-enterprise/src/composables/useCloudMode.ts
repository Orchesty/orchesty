import { ref, readonly } from 'vue'
import { BACKEND_URL } from '@/config'

const cloudMode = ref(false)
const cloudUrl = ref('')
const loaded = ref(false)

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
      }
    } catch {
      cloudMode.value = false
      cloudUrl.value = ''
    }

    loaded.value = true
  }

  return {
    cloudMode: readonly(cloudMode),
    cloudUrl: readonly(cloudUrl),
    loaded: readonly(loaded),
    loadCloudMode,
  }
}
