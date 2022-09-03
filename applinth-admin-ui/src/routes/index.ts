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
    path: "/profile",
    name: Routes.Profile,
    component: () => import("../views/ProfilePage.vue"),
    meta: {
      auth: ViewAuth.Private,
      breadcrumbs: [
        {
          text: "navigation.item.integrations",
          to: null,
        },
        {
          text: "navigation.item.profile",
          to: { name: Routes.Profile },
        },
      ],
    },
  },
];

const authRoutes = [
  {
    path: "/login",
    name: Routes.Login,
    component: () => import("../views/LoginPage.vue"),
    meta: {
      auth: ViewAuth.Public,
    },
  },
  {
    path: "/lost-password",
    name: Routes.ForgotPassword,
    component: () => import("../views/ForgotPasswordPage.vue"),
    meta: {
      auth: ViewAuth.Public,
    },
  },
  {
    path: "/reset-password",
    name: Routes.ResetPassword,
    component: () => import("../views/ResetPasswordPage.vue"),
    meta: {
      auth: ViewAuth.Public,
    },
  },
  {
    path: "/new-password/:token",
    name: Routes.NewPassword,
    component: () => import("../views/NewPasswordPage.vue"),
    meta: {
      auth: ViewAuth.Public,
    },
  },
  {
    path: "/password-changed",
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
    breadcrumbs: [
      {
        text: "navigation.item.integrations",
        to: null,
      },
      {
        text: "navigation.item.overview",
        to: Routes.Overview,
      },
    ],
  },
};

const applications = [
  {
    path: "/application/:id",
    name: Routes.ApplicationDetail,
    component: () => import("../views/ApplicationDetailPage.vue"),
    meta: {
      auth: ViewAuth.Private,
      breadcrumbs: (appActiveName: string) => {
        return [
          {
            text: "navigation.item.integrations",
            to: null,
          },
          {
            text: 'Application Item',
            to: { name: Routes.ApplicationDetail },
          },
        ];
      },
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
      breadcrumbs: [
        {
          text: "navigation.item.integrations",
          to: null,
        },
        {
          text: "navigation.item.users",
          to: { name: Routes.Users },
        },
      ],
    },
  },
  {
    path: "/user/:id/update",
    name: Routes.UserUpdate,
    component: () => import("../views/UserUpdatePage.vue"),
    meta: {
      auth: ViewAuth.Private,
      breadcrumbs: [
        {
          text: "navigation.item.integrations",
          to: null,
        },
        {
          text: "navigation.item.users",
          to: { name: Routes.Users },
        },
        {
          text: "navigation.item.userUpdate",
          to: { name: Routes.UserUpdate },
        },
      ],
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
      breadcrumbs: [
        {
          text: "navigation.item.integrations",
          to: null,
        },
        {
          text: "navigation.item.customers",
          to: { name: Routes.Customers },
        },
      ],
    },
  },
  {
    path: "/customer/:id/billing",
    name: Routes.CustomerBilling,
    component: () => import("../views/CustomerBillingPage.vue"),
    meta: {
      auth: ViewAuth.Private,
      breadcrumbs: [
        {
          text: "navigation.item.integrations",
          to: null,
        },
        {
          text: "navigation.item.customers",
          to: { name: Routes.Customers },
        },
        {
          text: "navigation.item.customerBilling",
          to: { name: Routes.CustomerBilling },
        },
      ],
    },
  },
  {
    path: "/customer/:id/detail",
    name: Routes.CustomerDetail,
    component: () => import("../views/CustomerDetailPage.vue"),
    meta: {
      auth: ViewAuth.Private,
      breadcrumbs: [
        {
          text: "navigation.item.integrations",
          to: null,
        },
        {
          text: "navigation.item.customers",
          to: { name: Routes.Customers },
        },
        {
          text: "navigation.item.customerDetail",
          to: { name: Routes.CustomerDetail },
        },
      ],
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
