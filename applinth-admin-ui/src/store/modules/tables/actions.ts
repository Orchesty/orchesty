import {
  Actions,
  TableChangePagingPayload,
  TableFetchPayload,
  TableFilterPayload,
  TableRefreshPayload,
  TableSearchPayload,
  TableSortPayload,
} from "../../../types";
import { TableState } from "./state";
import { tablesConfig } from "./tablesConfig";
import { TablesActions, TablesMutations } from "./types";

export const actions: Actions<TablesActions, TableState> = {
  async fetch({ commit }, payload: TableFetchPayload): Promise<TableState> {
    const tableConfig = tablesConfig[payload.namespace];
    // TODO call backend API
    // const { data } = await apiClient.callGraphqlPrivate<
    //   any,
    //   TableQueryVariables
    // >({
    //   ...tableConfig.apiConfig,
    //   id: payload.ownRequestId || `TABLE/${payload.namespace}`,
    //   variables: {
    //     input: {
    //       filter: payload.filter ? payload.filter : [],
    //       pager: payload.pager,
    //       search: payload.search ? payload.search : "",
    //       sorter: payload.sorter ? payload.sorter : [],
    //     },
    //   },
    // });
    // const tableState = tableConfig.reduceData(data);
    const tableState: TableState = {
      items: [],
      filter: {},
      sorter: {},
      pager: {
        page: 0,
        size: 0,
        total: null,
        previous: null,
        next: null,
        last: null,
      },
      search: "",
    };
    commit(TablesMutations.Update, tableState);
    return tableState;
  },
  async filter({ commit, state }, payload: TableFilterPayload): Promise<void> {
    const tableConfig = tablesConfig[payload.namespace];
    // TODO call backend API
    // const { data } = await apiClient.callGraphqlPrivate<
    //   any,
    //   TableQueryVariables
    // >({
    //   ...tableConfig.apiConfig,
    //   id: `TABLE/${payload.namespace}`,
    //   variables: {
    //     input: {
    //       filter: payload.filter ? payload.filter : [],
    //       pager: {
    //         page: state.pager.page,
    //         size: state.pager.size,
    //       },
    //       search: state.search ? state.search : "",
    //       sorter: state.sorter ? state.sorter : [],
    //     },
    //   },
    // });
    // const newTableState: TableState = tableConfig.reduceData(data);
    const newTableState: TableState = {
      items: [],
      filter: {},
      sorter: {},
      pager: {
        page: 0,
        size: 0,
        total: null,
        previous: null,
        next: null,
        last: null,
      },
      search: "",
    };
    commit(TablesMutations.Update, newTableState);
  },
  async search({ commit, state }, payload: TableSearchPayload): Promise<void> {
    const tableConfig = tablesConfig[payload.namespace];
    // TODO call backend API
    // const { data } = await apiClient.callGraphqlPrivate<
    //   any,
    //   TableQueryVariables
    // >({
    //   ...tableConfig.apiConfig,
    //   id: `TABLE/${payload.namespace}`,
    //   variables: {
    //     input: {
    //       filter: state.filter ? state.filter : [],
    //       pager: {
    //         page: state.pager.page,
    //         size: state.pager.size,
    //       },
    //       search: state.search ? state.search : "",
    //       sorter: state.sorter ? state.sorter : [],
    //     },
    //   },
    // });
    // const newTableState: TableState = tableConfig.reduceData(data);
    const newTableState: TableState = {
      items: [],
      filter: {},
      sorter: {},
      pager: {
        page: 0,
        size: 0,
        total: null,
        previous: null,
        next: null,
        last: null,
      },
      search: "",
    };
    commit(TablesMutations.Update, newTableState);
  },
  async refresh(
    { commit, state },
    payload: TableRefreshPayload
  ): Promise<void> {
    const tableConfig = tablesConfig[payload.namespace];
    // TODO call backend API
    // const { data } = await apiClient.callGraphqlPrivate<
    //   any,
    //   TableQueryVariables
    // >({
    //   ...tableConfig.apiConfig,
    //   id: payload.hideLoading
    //     ? `TABLE_REFRESH/${payload.namespace}`
    //     : `TABLE/${payload.namespace}`,
    //   variables: {
    //     input: {
    //       filter: state.filter ? state.filter : [],
    //       pager: {
    //         page: state.pager.page,
    //         size: state.pager.size,
    //       },
    //       search: state.search ? state.search : "",
    //       sorter: state.sorter ? state.sorter : [],
    //     },
    //   },
    // });
    // const newTableState: TableState = tableConfig.reduceData(data);
    const newTableState: TableState = {
      items: [],
      filter: {},
      sorter: {},
      pager: {
        page: 0,
        size: 0,
        total: null,
        previous: null,
        next: null,
        last: null,
      },
      search: "",
    };
    commit(TablesMutations.Update, newTableState);
  },
  async changePaging(
    { commit, state },
    payload: TableChangePagingPayload
  ): Promise<void> {
    const tableConfig = tablesConfig[payload.namespace];
    // TODO call backend API
    // const { data } = await apiClient.callGraphqlPrivate<
    //   any,
    //   TableQueryVariables
    // >({
    //   ...tableConfig.apiConfig,
    //   id: `TABLE/${payload.namespace}`,
    //   variables: {
    //     input: {
    //       filter: state.filter ? state.filter : [],
    //       pager: payload.pager,
    //       search: state.search ? state.search : "",
    //       sorter: state.sorter ? state.sorter : [],
    //     },
    //   },
    // });
    // const newTableState: TableState = tableConfig.reduceData(data);
    const newTableState: TableState = {
      items: [],
      filter: {},
      sorter: {},
      pager: {
        page: 0,
        size: 0,
        total: null,
        previous: null,
        next: null,
        last: null,
      },
      search: "",
    };
    commit(TablesMutations.Update, newTableState);
  },
  resetPaging({ commit, state }): void {
    const newTableState: TableState = {
      ...state,
      pager: { ...state.pager, page: 1, size: 10 },
    };
    commit(TablesMutations.Update, newTableState);
  },
  async sort({ commit, state }, payload: TableSortPayload): Promise<void> {
    const tableConfig = tablesConfig[payload.namespace];
    // TODO call backend API
    // const { data } = await apiClient.callGraphqlPrivate<
    //   any,
    //   TableQueryVariables
    // >({
    //   ...tableConfig.apiConfig,
    //   id: `TABLE/${payload.namespace}`,
    //   variables: {
    //     input: {
    //       filter: state.filter ? state.filter : [],
    //       pager: {
    //         page: state.pager.page,
    //         size: state.pager.size,
    //       },
    //       search: state.search ? state.search : "",
    //       sorter: payload.sorter,
    //     },
    //   },
    // });
    // const newTableState: TableState = tableConfig.reduceData(data);
    const newTableState: TableState = {
      items: [],
      filter: {},
      sorter: {},
      pager: {
        page: 0,
        size: 0,
        total: null,
        previous: null,
        next: null,
        last: null,
      },
      search: "",
    };
    commit(TablesMutations.Update, newTableState);
  },
};
