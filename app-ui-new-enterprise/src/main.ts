import { createApp } from 'vue'
import { createPinia } from 'pinia'
import 'flowbite'
import 'rete-editor/style.css'

import App from './App.vue'
import { createEnterpriseRouter } from './router'
import { useAuthStore } from '@/stores/auth'
import './assets/css/main.css'

const app = createApp(App)

const pinia = createPinia()
app.use(pinia)
app.use(createEnterpriseRouter())

const authStore = useAuthStore()
authStore.initializeAuth()

app.mount('#app')
