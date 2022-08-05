import { Getters } from "../../../types";
import { Admin } from "../../../types/gqlGeneratedPublic";
import { AuthState } from "./state";
import { AuthGetters } from "./types";

export const getters: Getters<AuthGetters, AuthState> = {
  getAccessToken(state): AuthState["accessToken"] {
    return state.accessToken;
  },
  getRawSettings(state): string {
    return state.administrator?.settings || "{}";
  },
  getAdministrator(state): Admin {
    return (
      state.administrator ?? {
        isSuperAdmin: false,
        surname: "",
        firstname: "",
        username: "",
        id: -1,
      }
    );
  },
  getFullName(state): string {
    const admin = state.administrator;
    return admin ? `${admin.firstname} ${admin.surname}` : "Neznámé";
  },
};
