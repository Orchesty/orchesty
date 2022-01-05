import Vue from 'vue'
import Vuex from 'vuex'
import requests from './modules/api'
import auth from './modules/auth'
import notifications from './modules/notifications'
import userTasks from './modules/userTasks'
import flashMessages from './modules/flashMessages'
import adminUsers from './modules/adminUsers'
import topologies from './modules/topologies'
import trash from './modules/trash'
import { STORE } from './types'
import { resetModules } from './utils'
import { DATA_GRIDS } from './grid/grids'
import createGrid from './grid/store'
import { callGraphQL } from '../services/graphql'
import { callApi } from '../services/fetch'
import implementations from './modules/implementations'
import appStore from './modules/appStore'

Vue.use(Vuex)

export const createStore = (router) => {
  return new Vuex.Store({
    actions: {
      [STORE.ACTIONS.RESET]: ({ commit, state }) => {
        resetModules(commit, state)
      },
      [STORE.ACTIONS.CALL_GRAPHQL]: (store, payload) => {
        return callGraphQL({ ...payload, store })
      },
      [STORE.ACTIONS.CALL_API]: (store, payload) => {
        return callApi({ ...payload, store })
      },
      [STORE.ACTIONS.ROUTER_PUSH]: async (store, payload) => {
        await router.push(payload).catch(() => {})
      },
    },
    modules: {
      requests,
      auth,
      notifications,
      flashMessages,
      adminUsers,
      topologies,
      implementations,
      appStore,
      userTasks,
      trash,
      [DATA_GRIDS.ADMIN_USERS_LIST]: createGrid(DATA_GRIDS.ADMIN_USERS_LIST, {
        paging: {
          page: 1,
          itemsPerPage: 50,
        },
      }),
      [DATA_GRIDS.OVERVIEW]: createGrid(DATA_GRIDS.OVERVIEW, {
        paging: {
          page: 1,
          itemsPerPage: 50,
        },
      }),
      [DATA_GRIDS.STATISTICS]: createGrid(DATA_GRIDS.STATISTICS, {
        paging: {
          page: 1,
          itemsPerPage: 50,
        },
      }),
      [DATA_GRIDS.IMPLEMENTATIONS_LIST]: createGrid(DATA_GRIDS.IMPLEMENTATIONS_LIST, {
        paging: {
          page: 1,
          itemsPerPage: 50,
        },
      }),
      [DATA_GRIDS.SCHEDULED_TASK]: createGrid(DATA_GRIDS.SCHEDULED_TASK, {
        paging: {
          page: 1,
          itemsPerPage: 50,
        },
      }),
      [DATA_GRIDS.TRASH]: createGrid(DATA_GRIDS.TRASH, {
        filter: [[{ column: 'type', operator: 'EQ', value: ['trash'], default: true }]],
        paging: {
          page: 1,
          itemsPerPage: 10,
        },
      }),
      [DATA_GRIDS.NOTIFICATIONS]: createGrid(DATA_GRIDS.NOTIFICATIONS, {
        paging: {
          page: 1,
          itemsPerPage: 50,
        },
      }),
      [DATA_GRIDS.TOPOLOGY_LOGS]: createGrid(DATA_GRIDS.TOPOLOGY_LOGS, {
        filter: [[{ column: 'topology_id', operator: 'EQUAL', value: [''], default: true }]],
        paging: {
          page: 1,
          itemsPerPage: 50,
        },
      }),
      [DATA_GRIDS.LOGS]: createGrid(DATA_GRIDS.LOGS, {
        paging: {
          page: 1,
          itemsPerPage: 10,
        },
      }),
      [DATA_GRIDS.EVENTS]: createGrid(DATA_GRIDS.EVENTS, {
        paging: {
          page: 1,
          itemsPerPage: 50,
        },
      }),
      [DATA_GRIDS.INSTALLED_APPS]: createGrid(DATA_GRIDS.INSTALLED_APPS, {
        paging: {
          page: 1,
          itemsPerPage: 50,
        },
      }),
      [DATA_GRIDS.AVAILABLE_APPS]: createGrid(DATA_GRIDS.AVAILABLE_APPS, {
        paging: {
          page: 1,
          itemsPerPage: 50,
        },
      }),
      [DATA_GRIDS.USER_TASK]: createGrid(DATA_GRIDS.USER_TASK, {
        filter: [
          [{ column: 'type', operator: 'EQ', value: ['userTask'], default: true }],
          [{ column: 'topologyId', operator: 'EQ', value: [''], default: true }],
        ],
        paging: {
          page: 1,
          itemsPerPage: 10,
        },
      }),
    },
  })
}
