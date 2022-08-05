import { api } from "../../../api";
import { TLoginForm } from "../../../components/auth/types";
import { ErrorType } from "../../../enums";
import { Actions } from "../../../types";
import {
  LoginMutation,
  LoginMutationVariables,
  RefreshTokenMutation,
  RefreshTokenMutationVariables,
} from "../../../types/gqlGeneratedPublic";
import { apiClient, AuthenticationData, authService } from "../../../utils";
import { AuthState } from "./state";
import { AuthActions, AuthMutations } from "./types";

export const actions: Actions<AuthActions, AuthState> = {
  async login({ commit }, payload: TLoginForm): Promise<boolean> {
    try {
      const { data } = await apiClient.callGraphqlPublic<
        LoginMutation,
        LoginMutationVariables
      >({
        ...api.auth.login,
        throwError: true,
        variables: {
          username: payload.email,
          password: payload.password,
        },
      });
      if (!data) {
        throw new Error(
          `Login mutation is supposed to return data but got: ${data}`
        );
      }
      commit(AuthMutations.SetAdministrator, {
        ...data.login.admin,
        adminId: data.login.adminId,
      });
      authService.authenticate({
        accessToken: data.login.accessToken ?? "",
        expiresIn: data.login.expiresIn ?? 1,
      });
      return true;
    } catch (e) {
      return false;
    }
  },
  async refreshToken({ commit }): Promise<AuthenticationData | null> {
    try {
      const { data } = await apiClient.callGraphqlPublic<
        RefreshTokenMutation,
        RefreshTokenMutationVariables
      >({
        ...api.auth.refreshToken,
        errorType: ErrorType.None,
        throwError: true,
      });
      if (!data) {
        throw new Error(
          `Refresh token mutation is supposed to return data but got: ${data}`
        );
      }
      commit(AuthMutations.SetAccessToken, data.refreshToken.accessToken);
      commit(AuthMutations.SetAdministrator, {
        ...data.refreshToken.admin,
        adminId: data.refreshToken.adminId,
      });
      return {
        accessToken: data.refreshToken.accessToken ?? "",
        expiresIn: data.refreshToken.expiresIn ?? 1,
      };
    } catch {
      return null;
    }
  },
  async updateSettings({ state, commit }, payload: string): Promise<any> {
    const administrator = state.administrator;
    if (!administrator) {
      throw new Error("This should not ever happen (NO ADMINISTRATOR DATA)");
    }
    const { data } = await apiClient.callGraphqlPublic<any, any>({
      ...api.auth.login, // TODO: implement correctly
      variables: { payload },
    });
    if (data) {
      commit(AuthMutations.SetAdministrator, data.updateLoggedAdministrator);
    }
    return data;
  },
};
