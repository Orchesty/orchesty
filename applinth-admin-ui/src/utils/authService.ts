import { Routes } from "../enums";
import store from "../store";
import { router } from "./router";

export interface AuthenticationData {
  accessToken: string;
  expiresIn: number;
}

export function invalidateAuthentication() {
  store.commit("resetStore");
  router.push({ name: Routes.Login });
}
