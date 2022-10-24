import "@mdi/font/css/materialdesignicons.css"
import "material-design-icons-iconfont/dist/material-design-icons.css"
import Vue from "vue"
import App from "./App.vue"
import { initializeFirebaseAuth } from "./firebase"
import store from "./store"
import { i18n, router, vuetify } from "./utils"
import "./utils/veeValidate"

Vue.config.productionTip = false

let app: Vue | null = null

initializeFirebaseAuth(() => {
  if (!app) {
    app = new Vue({
      router,
      store,
      vuetify,
      i18n,
      render: (h) => h(App),
    }).$mount("#app")
  }
})
