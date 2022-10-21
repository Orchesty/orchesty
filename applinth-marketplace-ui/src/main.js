import Vue from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'
import vuetify from './plugins/vuetify'
import { i18n } from '@/localization'
import { config } from '@/config'
import {
  setInteractionMode,
  ValidationObserver,
  ValidationProvider,
} from 'vee-validate'

setInteractionMode('eager')

Vue.component('ValidationProvider', ValidationProvider)
Vue.component('ValidationObserver', ValidationObserver)
Vue.config.productionTip = false

async function prepareInitialization() {
  // Initialize Mock Service Worker only in dev mode
  if (config.msw) {
    const { startWorker } = await import('./mocks/browser.js')
    return await startWorker()
  }
}

prepareInitialization().then(() => {
  new Vue({
    router,
    store,
    i18n,
    vuetify,
    render: (h) => h(App),
  }).$mount('#app')
})
