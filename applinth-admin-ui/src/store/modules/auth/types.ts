export const authNamespace = "auth";

export enum AuthActions {
  Login = "login",
  UpdateSettings = "updateSettings",
  ChangePassword = "changePassword",
  Reauthenticate = "reauthenticate",
  SendResetPasswordLink = "sendResetPasswordLink",
  Logout = "logout",
}

export enum AuthMutations {
  SetUser = "setUser",
  SetAccessToken = "setAccessToken",
  LogoutUser = "logoutUser",
}

export enum AuthGetters {
  GetUser = "getUser",
  GetDisplayName = "getDisplayName",
  GetAccessToken = "getAccessToken",
}

export type User = {
  id: string;
  name: string | null;
  email: string | null;
  tenantId: string | null;
};
