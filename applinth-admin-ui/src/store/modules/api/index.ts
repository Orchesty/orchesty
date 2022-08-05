import { createState } from "./state";
import { mutations } from "./mutations";
import { getters } from "./getters";

export * from "./types";
export * from "./state";

export const apiModule = {
  namespaced: true,
  state: createState(),
  mutations,
  getters,
};
