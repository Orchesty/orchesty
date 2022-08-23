import { Mutations } from "../../../types";
import { AuthState } from "./state";
import { AuthMutations } from "./types";

export const mutations: Mutations<AuthMutations, AuthState> = {
  setUser(state, payload: AuthState["user"]) {
    state.user = payload;
  },
  setAccessToken(state, payload: AuthState["accessToken"]) {
    state.accessToken = payload;
  },
};
