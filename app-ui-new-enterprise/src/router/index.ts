import type { RouteRecordRaw } from 'vue-router'
import { coreRoutes, createAppRouter } from '@orchesty/ui-core'

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
  return createAppRouter(mergeRoutes(coreRoutes))
}
