import { Mutations } from "../../../types";
import { TableState } from "./state";
import { TablesMutations } from "./types";

export const mutations: Mutations<TablesMutations, TableState> = {
  update(state, payload: TableState): void {
    state.items = payload.items;
    state.filter = payload.filter;
    state.pager = payload.pager;
    state.search = payload.search;
    state.sorter = payload.sorter;
  },
  itemsUpdate(state, payload: TableState["items"]): void {
    state.items = payload;
  },
};
