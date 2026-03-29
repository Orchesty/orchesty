import { createApp } from 'vue'
import { createPinia } from 'pinia'
import 'flowbite'
import 'rete-editor/style.css'

import App from './App.vue'
import { createEnterpriseRouter } from './router'
import { useAuthStore } from '@/stores/auth'
import { auth0Plugin, isAuth0Enabled } from '@/auth/auth0-plugin'
import './assets/css/main.css'

const app = createApp(App)

const pinia = createPinia()
app.use(pinia)

if (isAuth0Enabled && auth0Plugin) {
  app.use(auth0Plugin)
}

app.use(createEnterpriseRouter())

const authStore = useAuthStore()
authStore.initializeAuth()

app.mount('#app')
