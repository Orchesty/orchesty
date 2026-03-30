import { createApp } from 'vue'
import { createPinia } from 'pinia'
import 'flowbite'
import 'rete-editor/style.css'

import App from './App.vue'
import { createEnterpriseRouter } from './router'
import { useAuthStore } from '@/stores/auth'
import { auth0Plugin, isAuth0Enabled } from '@/auth/auth0-plugin'
import { useCloudMode } from '@/composables/useCloudMode'
import './assets/css/main.css'

const BACKEND_URL = import.meta.env.VITE_BACKEND_URL || 'http://127.0.0.66:8085'

async function handleCloudAuthHandoff(): Promise<boolean> {
  const params = new URLSearchParams(window.location.search)
  const cloudAuth = params.get('cloud_auth')
  if (!cloudAuth) return false

  params.delete('cloud_auth')
  const cleanUrl =
    window.location.pathname + (params.toString() ? `?${params}` : '') + window.location.hash
  window.history.replaceState({}, '', cleanUrl)

  try {
    const res = await fetch(`${BACKEND_URL}/api/cloud/session-handoff`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token: cloudAuth }),
    })

    if (!res.ok) {
      console.error('[CloudAuth] Handoff failed:', res.status)
      sessionStorage.setItem('cloud_handoff_failed', 'true')
      return false
    }

    const data = await res.json()
    if (data.token) {
      localStorage.setItem('auth_token', data.token)
      localStorage.setItem(
        'auth_user',
        JSON.stringify({ id: data.id, email: data.email, settings: data.settings || {} }),
      )
      localStorage.setItem('lastTokenRefreshTime', String(Date.now()))
      localStorage.setItem('cloud_handoff_session', 'true')
      sessionStorage.removeItem('cloud_handoff_failed')
      return true
    }
  } catch (err) {
    console.error('[CloudAuth] Handoff error:', err)
  }
  sessionStorage.setItem('cloud_handoff_failed', 'true')
  return false
}

async function bootstrap() {
  const app = createApp(App)

  const pinia = createPinia()
  app.use(pinia)

  const { loadCloudMode } = useCloudMode()
  await loadCloudMode()

  await handleCloudAuthHandoff()

  if (isAuth0Enabled && auth0Plugin) {
    app.use(auth0Plugin)
  }

  app.use(createEnterpriseRouter())

  const authStore = useAuthStore()
  authStore.initializeAuth()

  app.mount('#app')
}

bootstrap()
