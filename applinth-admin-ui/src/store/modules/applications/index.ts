import { createState } from "./state";
import { mutations } from "./mutations";
import { getters } from "./getters";

export * from "./state";
export * from "./types";

export const applicationsModule = {
  namespaced: true,
  state: createState(),
  mutations,
  getters,
};
