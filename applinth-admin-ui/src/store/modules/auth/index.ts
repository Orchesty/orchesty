import { createState } from "./state";
import { actions } from "./actions";
import { mutations } from "./mutations";
import { getters } from "./getters";

export * from "./state";
export * from "./types";

export const authModule = {
  namespaced: true,
  state: createState(),
  actions,
  mutations,
  getters,
};
