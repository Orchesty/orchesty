import Vue from "vue";
import Vuex from "vuex";
import {
  alertsNamespace,
  alertsModule,
  createState as alertsCreateState,
} from "./modules/alerts";
import {
  authNamespace,
  authModule,
  createState as authCreateState,
} from "./modules/auth";
import {
  apiNamespace,
  apiModule,
  createState as apiCreateState,
} from "./modules/api";
import {
  applicationsNamespace,
  applicationsModule,
  createState as applicationsCreateState,
} from "./modules/applications";

Vue.use(Vuex);

export default new Vuex.Store({
  state: {},
  actions: {},
  mutations: {
    resetStore(state: any) {
      state[alertsNamespace] = alertsCreateState();
      state[authNamespace] = authCreateState();
      state[apiNamespace] = apiCreateState();
      state[applicationsNamespace] = applicationsCreateState();
    },
  },
  modules: {
    [alertsNamespace]: alertsModule,
    [authNamespace]: authModule,
    [apiNamespace]: apiModule,
    [applicationsNamespace]: applicationsModule,
  },
});
