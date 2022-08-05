import Vue from "vue";
import App from "./App.vue";
import store from "./store";
import { vuetify, i18n, router } from "./utils";
import "./utils/veeValidate";
import "@mdi/font/css/materialdesignicons.css";
import "material-design-icons-iconfont/dist/material-design-icons.css";

Vue.config.productionTip = false;

new Vue({
  router,
  store,
  vuetify,
  i18n,
  render: (h) => h(App),
}).$mount("#app");
