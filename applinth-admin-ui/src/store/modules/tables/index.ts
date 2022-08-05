import { createState } from "./state";
import { actions } from "./actions";
import { mutations } from "./mutations";
import { getters } from "./getters";

export * from "./types";
export * from "./state";
export * from "./tablesConfig";

export const createTablesModule = () => ({
  namespaced: true,
  state: createState(),
  actions,
  mutations,
  getters,
});
