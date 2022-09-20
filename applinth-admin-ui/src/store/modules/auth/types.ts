export const authNamespace = "auth";

export enum AuthActions {
  Login = "login",
  UpdateSettings = "updateSettings",
  ChangePassword = "changePassword",
  Reauthenticate = "reauthenticate",
}

export enum AuthMutations {
  SetUser = "setUser",
  SetAccessToken = "setAccessToken",
}

export enum AuthGetters {
  GetUser = "getUser",
  GetDisplayName = "getDisplayName",
  GetAccessToken = "getAccessToken",
  GetRawSettings = "getRawSettings",
}

export type User = {
  id: string;
  name: string | null;
  email: string | null;
  tenantId: string | null;
};
