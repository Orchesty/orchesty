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
  TablesNamespaces,
  createTablesModule,
  createState as tableCreateState,
} from "./modules/tables";

Vue.use(Vuex);

function getTableModules() {
  const tableModules: any = {};
  Object.values(TablesNamespaces).forEach((tableNamespace) => {
    tableModules[tableNamespace] = createTablesModule();
  });
  return tableModules;
}

export default new Vuex.Store({
  state: {},
  actions: {},
  mutations: {
    resetStore(state: any) {
      Object.values(TablesNamespaces).forEach((tableNamespace) => {
        state[tableNamespace] = tableCreateState();
      });
      state[alertsNamespace] = alertsCreateState();
      state[authNamespace] = authCreateState();
      state[apiNamespace] = apiCreateState();
    },
  },
  modules: {
    ...getTableModules(),
    [alertsNamespace]: alertsModule,
    [authNamespace]: authModule,
    [apiNamespace]: apiModule,
  },
});
