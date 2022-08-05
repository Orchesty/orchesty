import { LocalStorage, Routes } from "../enums";
import store from "../store";
import {
  AuthActions,
  AuthGetters,
  AuthMutations,
  authNamespace,
} from "../store/modules/auth/types";
import { router } from "./router";

export interface AuthenticationData {
  accessToken: string;
  expiresIn: number;
}

export class AuthService {
  private expireTimeout: number | null = null;

  set accessToken(accessToken: string) {
    store.commit(
      `${authNamespace}/${AuthMutations.SetAccessToken}`,
      accessToken
    );
  }

  get accessToken(): string {
    return store.getters[`${authNamespace}/${AuthGetters.GetAccessToken}`];
  }

  set hasRefreshToken(hasRefreshToken: boolean) {
    if (hasRefreshToken) {
      localStorage.setItem(LocalStorage.HasRefreshToken, "true");
    } else {
      localStorage.removeItem(LocalStorage.HasRefreshToken);
    }
  }

  get hasRefreshToken(): boolean {
    return !!localStorage.getItem(LocalStorage.HasRefreshToken);
  }

  /**
   * Authenticates app user.
   */
  public authenticate(authenticationData: AuthenticationData): void {
    this.accessToken = authenticationData.accessToken;
    this.hasRefreshToken = true;
    this.setExpireTimeout(authenticationData.expiresIn);
  }

  /**
   * Invalidates authentication and resets store
   * @param redirect Whether redirect to login page
   */
  public invalidateAuthentication(redirect = false) {
    store.commit("resetStore");
    if (this.expireTimeout) clearTimeout(this.expireTimeout);
    this.expireTimeout = null;
    this.hasRefreshToken = false;
    this.accessToken = "";
    if (redirect) {
      router.push({ name: Routes.Login });
    }
  }

  /**
   * Returns true / false whether current user is authenticated.
   * It includes request for an authentication extension attempt.
   * @param redirect Whether redirect to login page in case refresh attempt fails
   */
  public isAuthenticatedOrRefresh(
    redirect = false
  ): Promise<boolean> | boolean {
    if (this.expireTimeout) {
      return true;
    } else if (this.hasRefreshToken) {
      return this.tryRefreshAuthentication(redirect);
    } else {
      return false;
    }
  }

  private setExpireTimeout(expiresIn: number): void {
    if (this.expireTimeout) {
      clearTimeout(this.expireTimeout);
    }
    this.expireTimeout = setTimeout(() => {
      this.tryRefreshAuthentication(true);
    }, expiresIn * 1000);
  }

  private async tryRefreshAuthentication(redirect: boolean): Promise<boolean> {
    const authenticationData: AuthenticationData = await store.dispatch(
      `${authNamespace}/${AuthActions.RefreshToken}`
    );
    if (authenticationData) {
      this.authenticate(authenticationData);
      return true;
    } else {
      this.invalidateAuthentication(redirect);
      return false;
    }
  }
}

export const authService = new AuthService();
