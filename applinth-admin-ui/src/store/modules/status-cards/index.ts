import { createState } from "./state";
import { mutations } from "./mutations";
import { getters } from "./getters";
import { actions } from "./actions";

export * from "./types";
export * from "./state";

export const statusCardsModule = {
  namespaced: true,
  state: createState(),
  mutations,
  getters,
  actions,
};
