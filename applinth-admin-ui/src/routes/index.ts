import { Assign } from "@/types";
import { RouteConfig } from "vue-router";
import { Routes, ViewAuth } from "@/enums";

interface RouteConfigExtension {
  name?: Routes;
  meta?: {
    auth: ViewAuth;
  };
}

type ExtendedRouteConfig = Assign<RouteConfig, RouteConfigExtension>;

const baseRoutes = [
  {
    path: "/",
    redirect: { name: Routes.Overview },
  },
  {
    path: "/profil",
    name: Routes.Profile,
    component: () => import("../views/ProfilePage.vue"),
    meta: {
      auth: ViewAuth.Private,
    },
  },
];

const authRoutes = [
  {
    path: "/prihlaseni",
    name: Routes.Login,
    component: () => import("../views/LoginPage.vue"),
    meta: {
      auth: ViewAuth.Public,
    },
  },
  {
    path: "/zapomenute-heslo",
    name: Routes.ForgotPassword,
    component: () => import("../views/ForgotPasswordPage.vue"),
    meta: {
      auth: ViewAuth.Public,
    },
  },
  {
    path: "/reset-hesla",
    name: Routes.ResetPassword,
    component: () => import("../views/ResetPasswordPage.vue"),
    meta: {
      auth: ViewAuth.Public,
    },
  },
  {
    path: "/nove-heslo/:token",
    name: Routes.NewPassword,
    component: () => import("../views/NewPasswordPage.vue"),
    meta: {
      auth: ViewAuth.Public,
    },
  },
  {
    path: "/heslo-zmeneno",
    name: Routes.ChangedPassword,
    component: () => import("../views/ChangedPasswordPage.vue"),
    meta: {
      auth: ViewAuth.Public,
    },
  },
];

const overview = {
  path: "/overview",
  name: Routes.Overview,
  component: () => import("../views/OverviewPage.vue"),
  meta: {
    auth: ViewAuth.Private,
  },
};

const applications = [
  {
    path: "/application/:id",
    name: Routes.ApplicationDetail,
    component: () => import("../views/ApplicationDetailPage.vue"),
    meta: {
      auth: ViewAuth.Private,
    },
  },
];

const usersRoutes = [
  {
    path: "/users",
    name: Routes.Users,
    component: () => import("../views/UsersPage.vue"),
    meta: {
      auth: ViewAuth.Private,
    },
  },
  {
    path: "/user/:id/update",
    name: Routes.UserUpdate,
    component: () => import("../views/UserUpdatePage.vue"),
    meta: {
      auth: ViewAuth.Private,
    },
  },
];

const customersRoutes = [
  {
    path: "/customers",
    name: Routes.Customers,
    component: () => import("../views/CustomersPage.vue"),
    meta: {
      auth: ViewAuth.Private,
    },
  },
  {
    path: "/customer/:id/billing",
    name: Routes.CustomerBilling,
    component: () => import("../views/CustomerBillingPage.vue"),
    meta: {
      auth: ViewAuth.Private,
    },
  },
  {
    path: "/customer/:id/detail",
    name: Routes.CustomerDetail,
    component: () => import("../views/CustomerDetailPage.vue"),
    meta: {
      auth: ViewAuth.Private,
    },
  },
];

const notFound = {
  path: "*",
  name: Routes.NotFound,
  component: () => import("../views/NotFoundPage.vue"),
};

export const routes: Array<ExtendedRouteConfig> = [
  ...baseRoutes,
  ...authRoutes,
  ...usersRoutes,
  ...customersRoutes,
  overview,
  ...applications,
  notFound,
];
