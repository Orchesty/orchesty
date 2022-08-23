import { i18n } from "@/utils/vueI18n";
import { Getters } from "../../../types";
import { AuthState } from "./state";
import { AuthGetters, User } from "./types";

export const getters: Getters<AuthGetters, AuthState> = {
  getUser(state: AuthState): User | null {
    return state.user;
  },
  getDisplayName(state: AuthState): string {
    return (
      (state.user?.name || state.user?.email) ??
      (i18n.t("login.unknownName") as string)
    );
  },
  getAccessToken(state: AuthState): string | null {
    return state.accessToken;
  },
  getRawSettings(): any {
    // TODO not implemented yet
  },
};
