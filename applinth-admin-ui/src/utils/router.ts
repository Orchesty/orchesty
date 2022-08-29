import store from "@/store";
import { AuthGetters, authNamespace } from "@/store/modules/auth";
import Vue from "vue";
import VueRouter, { RawLocation } from "vue-router";
import { Routes, ViewAuth } from "../enums";
import { routes } from "../routes";
import { i18n } from "./vueI18n";

Vue.use(VueRouter);

export const router = new VueRouter({
  mode: "history",
  routes,
});

export const routerHistory: RawLocation[] = [];

router.beforeEach(async (to, from, next) => {
  // Check auth
  if (to.meta?.auth === ViewAuth.Private || to.meta?.auth === undefined) {
    const authenticated: boolean =
      store.getters[`${authNamespace}/${AuthGetters.GetAccessToken}`];
    if (!authenticated && to.name !== Routes.Login) {
      next({ name: Routes.Login, query: { redirect: to.path } });
      return;
    }
  }
  // Log route into router history list
  routerHistory.push(from.path);
  next();
});

router.afterEach((to) => {
  document.title = `${i18n.t("pageTitle.siteName")} | ${i18n.t(
    `pageTitle.route.${to.name}`
  )}`;
});
