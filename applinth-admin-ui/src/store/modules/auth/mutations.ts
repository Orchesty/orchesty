import { Mutations } from "../../../types";
import { AuthState } from "./state";
import { AuthMutations } from "./types";

export const mutations: Mutations<AuthMutations, AuthState> = {
  setAccessToken(state, payload: AuthState["accessToken"]) {
    state.accessToken = payload;
  },
  setAdministrator(state, payload: AuthState["administrator"]) {
    state.administrator = payload;
  },
};
