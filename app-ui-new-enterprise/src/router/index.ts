import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { coreRoutes, createAppRouter } from '@orchesty/ui-core'
import { useAuth0 } from '@auth0/auth0-vue'
import { isAuth0Enabled } from '@/auth/auth0-plugin'
import { useAuthStore } from '@/stores/auth'
import { useCloudMode } from '@/composables/useCloudMode'
import { STORAGE_KEYS } from '@/config'

import EnterpriseDashboardLayout from '@/layouts/DashboardLayout.vue'
import DashboardView from '@/views/dashboard/DashboardView.vue'
import SignInView from '@/views/auth/SignInView.vue'
import SetupView from '@/views/auth/SetupView.vue'
import ForgotPasswordView from '@/views/auth/ForgotPasswordView.vue'
import ResetPasswordView from '@/views/auth/ResetPasswordView.vue'
import AcceptInviteView from '@/views/auth/AcceptInviteView.vue'

const enterpriseOnlyChildren: RouteRecordRaw[] = [
  {
    path: 'audit-logs',
    name: 'audit-logs',
    component: () => import('@/views/audit-logs/AuditLogsView.vue'),
  },
  {
    path: 'trace',
    name: 'trace',
    component: () => import('@/views/trace/TraceView.vue'),
  },
]

const enterpriseOverrides: Record<string, RouteRecordRaw> = {
  'app-layout': {
    path: '/',
    name: 'app-layout',
    component: EnterpriseDashboardLayout,
    children: [],
  },
  dashboard: {
    path: 'dashboard',
    name: 'dashboard',
    component: DashboardView,
  },
  setup: {
    path: '/setup',
    name: 'setup',
    component: SetupView,
  },
  'sign-in': {
    path: '/sign-in',
    name: 'sign-in',
    component: SignInView,
  },
  'forgot-password': {
    path: '/forgot-password',
    name: 'forgot-password',
    component: ForgotPasswordView,
  },
  'reset-password': {
    path: '/reset-password/:token',
    name: 'reset-password',
    component: ResetPasswordView,
    props: true,
  },
  'accept-invite': {
    path: '/accept-invite/:token',
    name: 'accept-invite',
    component: AcceptInviteView,
    props: true,
  },
  settings: {
    path: 'settings',
    name: 'settings',
    component: () => import('@/views/settings/SettingsView.vue'),
  },
  users: {
    path: 'users',
    name: 'users',
    component: () => import('@/views/users/UsersView.vue'),
  },
  'account-settings': {
    path: 'orchesty/account',
    name: 'account-settings',
    component: () => import('@/views/account/AccountSettingsView.vue'),
  },
  'topology-detail': {
    path: ':id',
    name: 'topology-detail',
    component: () => import('@/views/topologies/EnterpriseTopologyDetailView.vue'),
    props: true,
  },
}

function mergeRoutes(routes: RouteRecordRaw[]): RouteRecordRaw[] {
  return routes.map((route) => {
    const name = route.name as string | undefined
    if (name && enterpriseOverrides[name]) {
      const override = enterpriseOverrides[name]
      const mergedChildren = route.children
        ? [...mergeRoutes(route.children)]
        : []

      if (name === 'app-layout') {
        mergedChildren.push(...enterpriseOnlyChildren)
      }

      return mergedChildren.length > 0
        ? { ...override, children: mergedChildren }
        : override
    }
    if (route.children) {
      return { ...route, children: mergeRoutes(route.children) }
    }
    return route
  })
}

export { invalidateUsersExistCache } from '@orchesty/ui-core'

export function createEnterpriseRouter() {
  if (!isAuth0Enabled) {
    return createAppRouter(mergeRoutes(coreRoutes))
  }

  const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: mergeRoutes(coreRoutes),
  })

  let auth0CallbackHandled = false
  let backendVerified = false

  function waitForAuth0Loading(auth0: ReturnType<typeof useAuth0>): Promise<void> {
    if (!auth0.isLoading.value) return Promise.resolve()
    return new Promise<void>((resolve) => {
      const check = () => {
        if (!auth0.isLoading.value) resolve()
        else setTimeout(check, 50)
      }
      check()
    })
  }

  router.beforeEach(async (to, _from, next) => {
    const authStore = useAuthStore()
    const auth0 = useAuth0()
    const { cloudMode, cloudUrl, loaded: cloudLoaded } = useCloudMode()

    await waitForAuth0Loading(auth0)

    const hasPreExistingSession = authStore.isAuthenticated
    const effectivelyAuthenticated = auth0.isAuthenticated.value || hasPreExistingSession
    const handoffFailed = sessionStorage.getItem(STORAGE_KEYS.CLOUD_HANDOFF_FAILED) === 'true'
    const isCloudReady = cloudLoaded.value && cloudMode.value

    // Process Auth0 callback only if no pre-existing session (e.g. cloud handoff)
    // to avoid overwriting it with a stale Auth0 session from a different user.
    if (auth0.isAuthenticated.value && !auth0CallbackHandled && !hasPreExistingSession) {
      auth0CallbackHandled = true
      try {
        await authStore.handleAuth0Callback(auth0)
        backendVerified = true
      } catch (err: unknown) {
        backendVerified = false
        console.error('[Auth0 Router] handleAuth0Callback FAILED:', err)
      }
    }

    if (hasPreExistingSession && !backendVerified) {
      backendVerified = true
    }

    const publicRoutes = ['/sign-in', '/setup', '/forgot-password']
    const isPublicRoute =
      publicRoutes.includes(to.path) ||
      to.path.startsWith('/reset-password') ||
      to.path.startsWith('/accept-invite')
    const requiresAuth = !isPublicRoute

    // In cloud mode, redirect auth-related routes to the cloud sign-in
    const cloudAuthRoutes = ['/sign-in', '/forgot-password']
    const isCloudAuthRoute =
      cloudAuthRoutes.includes(to.path) || to.path.startsWith('/reset-password')

    if (isCloudReady && isCloudAuthRoute && !effectivelyAuthenticated && !handoffFailed) {
      const returnUrl = encodeURIComponent(window.location.origin)
      window.location.href = `${cloudUrl.value}/sign-in?redirect_to=${returnUrl}`
      return
    }

    if (requiresAuth && !effectivelyAuthenticated) {
      if (isCloudReady && !handoffFailed) {
        const returnUrl = encodeURIComponent(window.location.origin + to.fullPath)
        window.location.href = `${cloudUrl.value}/sign-in?redirect_to=${returnUrl}`
        return
      }
      sessionStorage.removeItem(STORAGE_KEYS.CLOUD_HANDOFF_FAILED)
      next('/sign-in')
      return
    }

    if (to.path === '/sign-in' && effectivelyAuthenticated && backendVerified) {
      next('/dashboard')
    } else {
      next()
    }
  })

  return router
}
