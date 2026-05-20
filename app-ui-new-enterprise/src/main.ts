import { createApp } from 'vue'
import { createPinia } from 'pinia'
import 'flowbite'
import 'rete-editor/style.css'

import App from './App.vue'
import { createEnterpriseRouter } from './router'
import { useAuthStore } from '@/stores/auth'
import { buildAuth0Plugin } from '@/auth/auth0-plugin'
import { useCloudMode } from '@/composables/useCloudMode'
import { handleCloudAuthHandoff } from '@/services/cloudAuthService'
import { TITLE } from '@/config'
import './assets/css/main.css'

async function bootstrap() {
  const app = createApp(App)

  const pinia = createPinia()
  app.use(pinia)

  // Order matters:
  //   1) loadCloudMode() — fetches /api/status; tells us whether we're a
  //      cloud-managed instance or a standalone deployment. Drives ALL
  //      subsequent auth wiring.
  //   2) handleCloudAuthHandoff() — consumes any ?cloud_auth= token that
  //      was set as the URL query by the cloud frontend redirect. Stores
  //      the JWT in localStorage + sets CLOUD_HANDOFF_SESSION flag.
  //   3) buildAuth0Plugin(cloudMode) — installs Auth0 ONLY in non-cloud
  //      mode. Cloud instances must never own an Auth0 client; see
  //      auth/auth0-plugin.ts for the rationale.
  //   4) Router + store init — both depend on the final auth wiring.
  const { loadCloudMode, cloudMode } = useCloudMode()
  await loadCloudMode()

  if (TITLE) {
    document.title = `${TITLE} - Orchesty`
  }

  await handleCloudAuthHandoff()

  const plugin = buildAuth0Plugin(cloudMode.value)
  if (plugin) {
    app.use(plugin)
  }

  app.use(createEnterpriseRouter())

  const authStore = useAuthStore()
  authStore.initializeAuth()

  app.mount('#app')
}

bootstrap()
