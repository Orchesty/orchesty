import Vue from "vue"
import VueRouter from "vue-router"
import { ROUTES } from "@/router/routes"
import OverviewPage from "@/views/OverviewPage"
import ApplicationsPage from "@/views/ApplicationsPage"
import TrashPage from "@/views/TrashPage"
import TrashDetail from "@/components/commons/TrashDetail"
import TrashNoData from "@/components/commons/TrashNoData"
import AppInstalledDetailPage from "@/views/AppInstalledDetailPage"
import AppAvailableDetailPage from "@/views/AppAvailableDetailPage"
import NotFoundPage from "@/views/NotFoundPage"
import NotLoggedInPage from "@/views/NotLoggedInPage"
import SettingsPage from "@/views/SettingsPage"
import { authService } from "@/utils/authService.js"
import { config } from "@/config"
import LogsPage from "@/views/LogsPage.vue"

Vue.use(VueRouter)

const routes = [
  {
    path: "",
    redirect: "/integrations",
  },
  {
    path: "/integrations",
    name: ROUTES.OVERVIEW,
    component: OverviewPage,
    meta: {
      auth: true,
      breadcrumbs: [
        {
          text: "navigation.item.integrations",
          to: { name: ROUTES.OVERVIEW },
        },
      ],
    },
  },
  {
    path: "/applications",
    name: ROUTES.APPLICATIONS,
    component: ApplicationsPage,
    meta: {
      auth: true,
      breadcrumbs: [
        {
          text: "navigation.item.integrations",
          to: { name: ROUTES.OVERVIEW },
        },
        {
          text: "navigation.item.applications",
          to: { name: ROUTES.APPLICATIONS },
        },
      ],
    },
  },
  {
    path: "/application-detail-installed/:id",
    name: ROUTES.APPLICATION_INSTALLED,
    component: AppInstalledDetailPage,
    meta: {
      auth: true,
      breadcrumbs: (appActiveName) => {
        return [
          {
            text: "navigation.item.integrations",
            to: { name: ROUTES.OVERVIEW },
          },
          {
            text: "navigation.item.applications",
            to: { name: ROUTES.APPLICATIONS },
          },
          {
            text: appActiveName,
            to: { name: ROUTES.APPLICATION_INSTALLED },
          },
        ]
      },
    },
  },
  {
    path: "/application-detail-available/:id",
    name: ROUTES.APPLICATION_AVAILABLE,
    component: AppAvailableDetailPage,
    meta: {
      auth: true,
      breadcrumbs: (appActiveName) => {
        return [
          {
            text: "navigation.item.integrations",
            to: { name: ROUTES.OVERVIEW },
          },
          {
            text: "navigation.item.applications",
            to: { name: ROUTES.APPLICATIONS },
          },
          {
            text: appActiveName,
            to: { name: ROUTES.APPLICATION_AVAILABLE },
          },
        ]
      },
    },
  },
  {
    path: "/trash",
    component: TrashPage,
    children: [
      {
        path: "",
        component: TrashNoData,
        name: ROUTES.TRASH,
        meta: {
          auth: true,
          breadcrumbs: [
            {
              text: "navigation.item.integrations",
              to: { name: ROUTES.OVERVIEW },
            },
            {
              text: "navigation.item.trash",
              to: { name: ROUTES.TRASH },
            },
          ],
        },
      },
      {
        path: ":id",
        component: TrashDetail,
        name: ROUTES.TRASH_DETAIL,
        meta: {
          auth: true,
          breadcrumbs: [
            {
              text: "navigation.item.integrations",
              to: { name: ROUTES.OVERVIEW },
            },
            {
              text: "navigation.item.trash",
              to: { name: ROUTES.TRASH },
            },
            {
              text: "navigation.item.trashDetail",
              to: { name: ROUTES.TRASH_DETAIL },
            },
          ],
        },
      },
    ],
  },
  {
    path: "/settings",
    name: ROUTES.SETTINGS,
    component: SettingsPage,
    meta: {
      auth: true,
      breadcrumbs: [
        {
          text: "navigation.item.integrations",
          to: { name: ROUTES.OVERVIEW },
        },
        {
          text: "navigation.item.settings",
          to: { name: ROUTES.SETTINGS },
        },
      ],
    },
  },
  {
    path: "/logs",
    name: ROUTES.LOGS,
    component: LogsPage,
    meta: {
      auth: true,
      breadcrumbs: [
        {
          text: "navigation.item.integrations",
          to: { name: ROUTES.OVERVIEW },
        },
        {
          text: "navigation.item.logs",
          to: { name: ROUTES.LOGS },
        },
      ],
    },
  },
  {
    path: "/not-logged-in",
    name: ROUTES.NOT_LOGGED_IN,
    component: NotLoggedInPage,
  },
  {
    path: "*",
    name: ROUTES.NOT_FOUND,
    component: NotFoundPage,
  },
]

const router = new VueRouter({
  mode: "history",
  routes,
})

router.beforeEach(async (to, _from, next) => {
  if (config.disableAuth) {
    next()
    return
  }
  const needsAuth = Boolean(to.meta?.auth)
  const hasTokenInQuery = Boolean(to.query?.u)
  if (needsAuth) {
    const hasAccessToken = await authService.isAuthenticatedOrRefresh(false)
    let authenticated = false
    if (hasAccessToken) {
      authenticated = true
    } else if (hasTokenInQuery) {
      authenticated = await authService.initialAuthentication(to.query.u)
      if (authenticated) {
        next({ path: to.path })
      }
    }
    if (!authenticated) {
      let hasValidAuthBacklink = false
      try {
        new URL(config.authBacklink)
        hasValidAuthBacklink = true
      } catch {
        console.warn(
          "The AUTH_BACKLINK configuration parameter is not properly set."
        )
      }
      if (hasValidAuthBacklink) {
        window.location.href = config.authBacklink
      } else {
        next({ name: ROUTES.NOT_LOGGED_IN })
      }
      return
    }
  }
  next()
})

export default router
