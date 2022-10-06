import { Mutations } from "../../../types";
import { AuthState, createState } from "./state";
import { AuthMutations } from "./types";

export const mutations: Mutations<AuthMutations, AuthState> = {
  setUser(state, payload: AuthState["user"]) {
    state.user = payload;
  },
  setAccessToken(state, payload: AuthState["accessToken"]) {
    state.accessToken = payload;
  },
  logoutUser(state) {
    const initState: AuthState = createState();

    for (const initStateKey in initState) {
      state[initStateKey as keyof AuthState] = initState[
        initStateKey as keyof AuthState
      ] as null;
    }
  },
};
