import { Getters } from "../../../types";
import { TableState } from "./state";
import { TableGetters } from "./types";

export const getters: Getters<TableGetters, TableState> = {
  getTotal(state): number {
    return state.pager.total ?? 0;
  },
  getItems(state): any[] {
    return state.items;
  },
};
