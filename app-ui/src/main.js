import "core-js/stable"
import "regenerator-runtime/runtime"
import Vue from "vue"
import App from "@/App.vue"
import router, { beforeEach } from "@/services/router"
import { createStore } from "@/store"
import { i18n } from "@/localization"
import {
  ValidationProvider,
  ValidationObserver,
  setInteractionMode,
} from "vee-validate"
import { vuetify, ability } from "@/config"
import { abilitiesPlugin } from "@casl/vue"
import "@/assets/scss/main.scss"

setInteractionMode("eager")

Vue.config.productionTip = false

Vue.component("ValidationProvider", ValidationProvider)
Vue.component("ValidationObserver", ValidationObserver)
Vue.use(abilitiesPlugin, ability)

const store = createStore(router)

router.beforeEach(beforeEach(store))

new Vue({
  router,
  store,
  i18n,
  vuetify,
  render: (h) => h(App),
}).$mount("#app")
