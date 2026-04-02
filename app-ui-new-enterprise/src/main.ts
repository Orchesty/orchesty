import { createApp } from 'vue'
import { createPinia } from 'pinia'
import 'flowbite'
import 'rete-editor/style.css'

import App from './App.vue'
import { createEnterpriseRouter } from './router'
import { useAuthStore } from '@/stores/auth'
import { auth0Plugin, isAuth0Enabled } from '@/auth/auth0-plugin'
import { useCloudMode } from '@/composables/useCloudMode'
import { handleCloudAuthHandoff } from '@/services/cloudAuthService'
import './assets/css/main.css'

async function bootstrap() {
  const app = createApp(App)

  const pinia = createPinia()
  app.use(pinia)

  const { loadCloudMode, cloudMode, instanceName } = useCloudMode()
  await loadCloudMode()

  if (cloudMode.value && instanceName.value) {
    document.title = `${instanceName.value} - Orchesty`
  }

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
