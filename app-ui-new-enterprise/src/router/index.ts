import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { coreRoutes, createAppRouter } from '@orchesty/ui-core'
import { useAuth0 } from '@auth0/auth0-vue'
import { isAuth0Active } from '@/auth/auth0-plugin'
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
    path: 'notifications',
    name: 'notifications',
    component: () => import('@/views/notifications/NotificationsView.vue'),
  },
  {
    path: 'notification-settings',
    name: 'notification-settings',
    component: () => import('@/views/notifications/NotificationSettingsView.vue'),
  },
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
    meta: { role: 'developer' },
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

// In cloud mode, NO local auth view may ever render. Visiting any of these
// paths immediately redirects to `${cloudUrl}/sign-in` so the entire auth
// surface (login, signup, forgot password, reset password, invite accept)
// stays on the cloud frontend where Auth0 lives.
const LOCAL_AUTH_PATHS = ['/sign-in', '/sign-up', '/setup', '/forgot-password'] as const
const LOCAL_AUTH_PATH_PREFIXES = ['/reset-password', '/accept-invite'] as const

function isLocalAuthPath(path: string): boolean {
  return (
    LOCAL_AUTH_PATHS.includes(path as typeof LOCAL_AUTH_PATHS[number]) ||
    LOCAL_AUTH_PATH_PREFIXES.some((prefix) => path.startsWith(prefix))
  )
}

// Build the cloud retry URL for unauthenticated visitors. We always include
// a `redirect_to` so the cloud can deep-link back to the requested page once
// session-handoff succeeds. The `handoff_retry` hint tells the cloud
// frontend "the previous handoff attempt failed, please show a retry-friendly
// state and reissue a fresh token". Crucially: handoffFailed is ONLY a hint
// for the cloud UI; it never changes our routing decision here. The previous
// implementation gated cloud redirects on `!handoffFailed`, which trapped
// users on the local /sign-in after a single failed handoff and produced the
// Auth0 callback-mismatch error reported in production.
function buildCloudSignInUrl(cloudUrl: string, returnPath: string, handoffFailed: boolean): string {
  const returnUrl = encodeURIComponent(window.location.origin + returnPath)
  const retry = handoffFailed ? '&handoff_retry=1' : ''
  return `${cloudUrl}/sign-in?redirect_to=${returnUrl}${retry}`
}

// When the user lands directly on /accept-invite/:token on the instance
// (e.g. by clicking an old pipes-style invite email), we route them to a
// dedicated cloud view that knows how to:
//   1. Verify the token against THIS instance via the cloud BE proxy.
//   2. Walk the invitee through cloud sign-in / sign-up.
//   3. Mint a session-handoff token that piggybacks the invite token
//      (`linkInviteToken`) so the instance accepts the invite atomically
//      on first contact.
//
// Returning a plain /sign-in here would lose the invite token, which is
// exactly the bug the previous redirect produced.
function buildCloudAcceptInviteUrl(cloudUrl: string, token: string): string {
  const origin = encodeURIComponent(window.location.origin)
  return `${cloudUrl}/accept-invite/${encodeURIComponent(token)}?instanceUrl=${origin}`
}

function extractInviteToken(path: string): string | null {
  // We don't have the parsed router params yet — this guard runs before
  // the route component resolves — so we pull the token out of the URL
  // ourselves. The route shape is `/accept-invite/:token` with optional
  // trailing segments (none today, but kept extensible).
  const match = path.match(/^\/accept-invite\/([^/?#]+)/)
  return match ? decodeURIComponent(match[1] ?? '') : null
}

// Anti-loop guard. If the user has been bounced to the cloud sign-in
// more than `BOUNCE_LIMIT` times within `BOUNCE_WINDOW_MS`, we stop
// bouncing and surface a local /auth-error page instead. Without this
// guard a misconfigured cloud↔instance link (e.g. cloud BE unreachable,
// audience mismatch on every token) puts the browser into an infinite
// instance ↔ cloud loop.
const BOUNCE_LIMIT = 3
const BOUNCE_WINDOW_MS = 15_000

function recordBounce(): { exceeded: boolean; count: number } {
  const now = Date.now()
  const lastAt = Number.parseInt(sessionStorage.getItem(STORAGE_KEYS.CLOUD_BOUNCE_AT) ?? '0', 10) || 0
  const lastCount = Number.parseInt(sessionStorage.getItem(STORAGE_KEYS.CLOUD_BOUNCE_COUNT) ?? '0', 10) || 0
  const withinWindow = now - lastAt < BOUNCE_WINDOW_MS
  const count = withinWindow ? lastCount + 1 : 1
  sessionStorage.setItem(STORAGE_KEYS.CLOUD_BOUNCE_AT, String(now))
  sessionStorage.setItem(STORAGE_KEYS.CLOUD_BOUNCE_COUNT, String(count))
  return { exceeded: count > BOUNCE_LIMIT, count }
}

function clearBounceCounter(): void {
  sessionStorage.removeItem(STORAGE_KEYS.CLOUD_BOUNCE_AT)
  sessionStorage.removeItem(STORAGE_KEYS.CLOUD_BOUNCE_COUNT)
}

// Cloud guard: applied to EVERY navigation when `cloudMode === true`,
// regardless of whether Auth0 is active. The cloud handoff flow doesn't
// depend on a local Auth0 client — the JWT lands in localStorage via
// `handleCloudAuthHandoff()` during bootstrap.
//
// Invariant: cloud-managed instances NEVER show /sign-in, /setup,
// /forgot-password, /reset-password, /accept-invite. All authentication
// goes through the cloud frontend; the instance only consumes the resulting
// session-handoff token.
async function runCloudGuard(
  to: { path: string; fullPath: string },
  ctx: { authStore: ReturnType<typeof useAuthStore>; cloudUrl: string; auth0?: ReturnType<typeof useAuth0> | null },
): Promise<{ kind: 'redirect-external'; href: string } | { kind: 'next'; path?: string } | { kind: 'continue' }> {
  const { authStore, cloudUrl, auth0 } = ctx
  const handoffFailed = sessionStorage.getItem(STORAGE_KEYS.CLOUD_HANDOFF_FAILED) === 'true'
  const localAuthState = authStore.isAuthenticated
  const auth0Authenticated = auth0?.isAuthenticated.value === true
  const isAuthenticated = localAuthState || auth0Authenticated

  if (to.path === '/auth-error') {
    return { kind: 'continue' }
  }

  // Successful navigation while authenticated proves we broke out of any
  // pending bounce loop — reset the counter so future, unrelated logouts
  // start from a clean window.
  if (isAuthenticated && !isLocalAuthPath(to.path)) {
    clearBounceCounter()
  }

  if (isLocalAuthPath(to.path)) {
    // Preserve invite tokens through the cloud bounce so we land on the
    // dedicated cloud accept-invite view, not a stripped /sign-in.
    if (to.path.startsWith('/accept-invite')) {
      const inviteToken = extractInviteToken(to.path)
      if (inviteToken) {
        return {
          kind: 'redirect-external',
          href: buildCloudAcceptInviteUrl(cloudUrl, inviteToken),
        }
      }
    }

    const bounce = recordBounce()
    if (bounce.exceeded) {
      return { kind: 'next', path: '/auth-error' }
    }
    return {
      kind: 'redirect-external',
      href: buildCloudSignInUrl(cloudUrl, '/', handoffFailed),
    }
  }

  if (!isAuthenticated) {
    const bounce = recordBounce()
    if (bounce.exceeded) {
      return { kind: 'next', path: '/auth-error' }
    }
    return {
      kind: 'redirect-external',
      href: buildCloudSignInUrl(cloudUrl, to.fullPath, handoffFailed),
    }
  }

  return { kind: 'continue' }
}

export function createEnterpriseRouter() {
  // Single router for both standalone and cloud-managed deployments. The old
  // code branched on `isAuth0Enabled` and fell back to the legacy core router
  // (which has NO cloud awareness) when Auth0 ENVs were absent — a major hole
  // for cloud instances that don't ship Auth0 ENVs (most of them, since the
  // Auth0 client lives on `cloud.orchesty.io`).
  const routes = mergeRoutes(coreRoutes)
  if (!routes.some((r) => r.path === '/auth-error')) {
    routes.push({
      path: '/auth-error',
      name: 'auth-error',
      component: AuthErrorView,
    })
  }

  const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes,
  })

  let auth0CallbackHandled = false
  let backendVerified = false

  function waitForAuth0Loading(auth0: ReturnType<typeof useAuth0> | null): Promise<void> {
    if (!auth0) return Promise.resolve()
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
    // useAuth0() throws if no plugin is installed; only call it when Auth0
    // is actually active (i.e. standalone non-cloud deployment).
    const auth0 = isAuth0Active() ? useAuth0() : null
    const { cloudMode, cloudUrl, loaded: cloudLoaded } = useCloudMode()
    const { enterpriseDashboards, traceAuditing, auditLogs, pulse } = useFeatures()

    await waitForAuth0Loading(auth0)

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

    // Cloud-mode short-circuit. This MUST run before any local auth logic so
    // we never accidentally render /sign-in or /setup on a cloud instance,
    // and we never call into Auth0 from a cloud origin.
    if (cloudLoaded.value && cloudMode.value) {
      const decision = await runCloudGuard(to, { authStore, cloudUrl: cloudUrl.value, auth0 })
      if (decision.kind === 'redirect-external') {
        window.location.href = decision.href
        return
      }
      if (decision.kind === 'next') {
        next(decision.path ?? '/')
        return
      }
      // 'continue' falls through to the standard authenticated-user flow
      // below (feature flags, role checks handled by core, etc.)
      next()
      return
    }

    // ---- Standalone (non-cloud) flow below ----

    if (to.path === '/auth-error') {
      next()
      return
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
    if (auth0 && loginFailed && auth0.isAuthenticated.value) {
      next('/auth-error')
      return
    }

    const hasPreExistingSession = authStore.isAuthenticated
    const auth0Authed = auth0?.isAuthenticated.value === true
    const effectivelyAuthenticated = auth0Authed || hasPreExistingSession

    if (auth0 && auth0.isAuthenticated.value && !auth0CallbackHandled && !hasPreExistingSession) {
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

    if (requiresAuth && !effectivelyAuthenticated) {
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
