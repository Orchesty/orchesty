import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { coreRoutes, createAppRouter } from '@orchesty/ui-core'
import { useAuth0 } from '@auth0/auth0-vue'
import { isAuth0Enabled } from '@/auth/auth0-plugin'
import { useAuthStore } from '@/stores/auth'
import { useCloudMode } from '@/composables/useCloudMode'
import { useFeatures } from '@/composables/useFeatures'
import { STORAGE_KEYS } from '@/config'
import { activateUser, setNewPassword, checkUsersExist } from '@/services/authService'
import api from '@/services/api'

import EnterpriseDashboardLayout from '@/layouts/DashboardLayout.vue'
import DashboardView from '@/views/dashboard/DashboardView.vue'
import SignInView from '@/views/auth/SignInView.vue'
import SetupView from '@/views/auth/SetupView.vue'
import ForgotPasswordView from '@/views/auth/ForgotPasswordView.vue'
import ResetPasswordView from '@/views/auth/ResetPasswordView.vue'
import AcceptInviteView from '@/views/auth/AcceptInviteView.vue'
import AuthErrorView from '@/views/auth/AuthErrorView.vue'

const enterpriseOnlyChildren: RouteRecordRaw[] = [
  {
    path: 'audit-logs',
    name: 'audit-logs',
    component: () => import('@/views/audit-logs/AuditLogsView.vue'),
    meta: { feature: 'auditLogs', permission: 'settings:read' },
  },
  {
    path: 'trace',
    name: 'trace',
    component: () => import('@/views/trace/TraceView.vue'),
    meta: { feature: 'traceAuditing', permission: 'trace:read' },
  },
  {
    path: 'resources',
    name: 'resources',
    component: () => import('@/views/resources/ResourcesView.vue'),
    meta: { role: 'system_manager' },
  },
  {
    path: 'limiter',
    name: 'limiter',
    component: () => import('@/views/limiter/LimiterView.vue'),
    meta: { role: 'system_manager' },
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
  'auth-error': {
    path: '/auth-error',
    name: 'auth-error',
    component: AuthErrorView,
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
  trash: {
    path: 'trash',
    name: 'trash',
    component: () => import('@/views/trash/EnterpriseFailedMessagesView.vue'),
  },
}

function mergeRoutes(routes: RouteRecordRaw[]): RouteRecordRaw[] {
  return routes.map((route) => {
    const name = route.name as string | undefined
    if (name && enterpriseOverrides[name]) {
      const override = enterpriseOverrides[name]
      const mergedMeta = { ...route.meta, ...override.meta }
      const mergedChildren = route.children
        ? [...mergeRoutes(route.children)]
        : []

      if (name === 'app-layout') {
        mergedChildren.push(...enterpriseOnlyChildren)
      }

      return mergedChildren.length > 0
        ? { ...override, meta: mergedMeta, children: mergedChildren }
        : { ...override, meta: mergedMeta }
    }
    if (route.children) {
      return { ...route, children: mergeRoutes(route.children) }
    }
    return route
  })
}

import { invalidateUsersExistCache as invalidateCoreCache } from '@orchesty/ui-core'

let _auth0UsersExistCache: { value: boolean | null } = { value: null }

export function invalidateUsersExistCache() {
  invalidateCoreCache()
  _auth0UsersExistCache.value = null
}

export function createEnterpriseRouter() {
  if (!isAuth0Enabled) {
    return createAppRouter(mergeRoutes(coreRoutes))
  }

  const routes = mergeRoutes(coreRoutes)
  routes.push({
    path: '/auth-error',
    name: 'auth-error',
    component: AuthErrorView,
  })

  const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes,
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

  async function verifyUserOnBackend(): Promise<boolean> {
    try {
      await api.get('/api/user/whoami')
      return true
    } catch {
      return false
    }
  }

  router.beforeEach(async (to, _from, next) => {
    const authStore = useAuthStore()
    const auth0 = useAuth0()
    const { cloudMode, cloudUrl, loaded: cloudLoaded } = useCloudMode()
    const { enterpriseDashboards, traceAuditing, auditLogs, pulse } = useFeatures()

    await waitForAuth0Loading(auth0)

    if (to.path === '/auth-error') {
      next()
      return
    }

    const featureKey = to.meta.feature as string | undefined
    if (featureKey) {
      const featureMap: Record<string, boolean> = {
        enterpriseDashboards: enterpriseDashboards.value,
        traceAuditing: traceAuditing.value,
        auditLogs: auditLogs.value,
        pulse: pulse.value,
      }
      if (!featureMap[featureKey]) {
        next('/dashboard')
        return
      }
    }

    if (_auth0UsersExistCache.value === null) {
      try {
        _auth0UsersExistCache.value = await checkUsersExist()
      } catch {
        _auth0UsersExistCache.value = true
      }
    }

    if (!_auth0UsersExistCache.value && to.path !== '/setup') {
      next('/setup')
      return
    }

    if (to.path === '/setup' && _auth0UsersExistCache.value) {
      next('/sign-in')
      return
    }

    const loginFailed = sessionStorage.getItem(STORAGE_KEYS.AUTH0_LOGIN_FAILED) === 'true'
    if (loginFailed && auth0.isAuthenticated.value) {
      next('/auth-error')
      return
    }

    const hasPreExistingSession = authStore.isAuthenticated
    const effectivelyAuthenticated = auth0.isAuthenticated.value || hasPreExistingSession
    const handoffFailed = sessionStorage.getItem(STORAGE_KEYS.CLOUD_HANDOFF_FAILED) === 'true'
    const isCloudReady = cloudLoaded.value && cloudMode.value

    if (auth0.isAuthenticated.value && !auth0CallbackHandled && !hasPreExistingSession) {
      auth0CallbackHandled = true
      try {
        await authStore.handleAuth0Callback(auth0)

        const userExists = await verifyUserOnBackend()
        if (!userExists) {
          sessionStorage.setItem(STORAGE_KEYS.AUTH0_LOGIN_FAILED, 'true')
          authStore.token = null
          authStore.user = null
          localStorage.removeItem(STORAGE_KEYS.AUTH_TOKEN)
          localStorage.removeItem(STORAGE_KEYS.AUTH_USER)
          next('/auth-error')
          return
        }

        backendVerified = true
        sessionStorage.removeItem(STORAGE_KEYS.AUTH0_LOGIN_FAILED)

        const pendingInviteToken = localStorage.getItem(STORAGE_KEYS.PENDING_INVITE_TOKEN)
        if (pendingInviteToken) {
          try {
            await activateUser(pendingInviteToken)
            await setNewPassword(pendingInviteToken, crypto.randomUUID())
          } catch (inviteErr) {
            console.error('[Auth0 Router] Failed to finalize invite:', inviteErr)
          } finally {
            localStorage.removeItem(STORAGE_KEYS.PENDING_INVITE_TOKEN)
          }
        }
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
      next(_auth0UsersExistCache.value ? '/sign-in' : '/setup')
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
