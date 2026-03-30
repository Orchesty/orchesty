import { ref, readonly } from 'vue'

const cloudMode = ref(false)
const cloudUrl = ref('')
const loaded = ref(false)

export function useCloudMode() {
  async function loadCloudMode() {
    if (loaded.value) return

    try {
      const baseURL = import.meta.env.VITE_BACKEND_URL || 'http://127.0.0.66:8085'
      const res = await fetch(`${baseURL}/api/status`, {
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
