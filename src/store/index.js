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
import { DATA_GRIDS } from '@/services/enums/dataGridEnums'
import createGrid from './modules/grid'
import { callGraphQL } from '../services/utils/graphql'
import { callApi } from '../services/utils/apiFetch'
import implementations from './modules/implementations'
import appStore from './modules/appStore'
import { DIRECTION, OPERATOR } from '@/services/enums/gridEnums'
import moment from 'moment'

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
        sorter: [
          {
            column: 'started',
            direction: DIRECTION.DESCENDING,
          },
        ],
        paging: {
          page: 1,
          itemsPerPage: 50,
        },
      }),
      [DATA_GRIDS.STATISTICS]: createGrid(DATA_GRIDS.STATISTICS, {
        sorter: [
          {
            column: 'id',
            direction: DIRECTION.DESCENDING,
          },
        ],
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
        sorter: [
          {
            column: 'updated',
            direction: DIRECTION.DESCENDING,
          },
        ],
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
        sorter: [
          {
            column: 'timestamp',
            direction: DIRECTION.DESCENDING,
          },
        ],
        filter: [[{ column: 'topology_id', operator: 'EQUAL', value: [''], default: true }]],
        paging: {
          page: 1,
          itemsPerPage: 50,
        },
      }),
      [DATA_GRIDS.LOGS]: createGrid(DATA_GRIDS.LOGS, {
        sorter: [
          {
            column: 'timestamp',
            direction: DIRECTION.DESCENDING,
          },
        ],
        paging: {
          page: 1,
          itemsPerPage: 10,
        },
      }),
      [DATA_GRIDS.NODE_LOGS]: createGrid(DATA_GRIDS.LOGS, {
        filter: [
          [{ column: 'topology_id', operator: 'EQUAL', value: [''], default: true }],
          [
            {
              column: 'node_id',
              operator: 'EQUAL',
              value: [''],
              default: true,
            },
          ],
        ],
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
      [DATA_GRIDS.HEALTH_CHECK_QUEUES]: createGrid(DATA_GRIDS.HEALTH_CHECK_QUEUES, {
        filter: [
          [
            {
              column: 'timestamp',
              operator: OPERATOR.BETWEEN,
              value: [moment().utc().subtract(1, 'minutes').format(), moment().utc().format()],
            },
          ],
        ],
        sorter: null,
        paging: {
          page: 1,
          itemsPerPage: 99999999,
        },
      }),
      [DATA_GRIDS.USER_TASK]: createGrid(DATA_GRIDS.USER_TASK, {
        sorter: [
          {
            column: 'updated',
            direction: DIRECTION.DESCENDING,
          },
        ],
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
