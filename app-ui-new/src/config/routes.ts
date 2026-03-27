import type { RouteRecordRaw } from 'vue-router'
import SignInView from '@/views/auth/SignInView.vue'
import SetupView from '@/views/auth/SetupView.vue'
import ForgotPasswordView from '@/views/auth/ForgotPasswordView.vue'
import ResetPasswordView from '@/views/auth/ResetPasswordView.vue'
import AcceptInviteView from '@/views/auth/AcceptInviteView.vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import DashboardView from '@/views/dashboard/DashboardView.vue'

export const coreRoutes: RouteRecordRaw[] = [
  {
    path: '/',
    redirect: '/dashboard',
  },
  {
    path: '/',
    name: 'app-layout',
    component: DashboardLayout,
    children: [
      {
        path: 'dashboard',
        name: 'dashboard',
        component: DashboardView,
      },
      {
        path: 'home',
        name: 'home',
        component: () => import('@/views/HomeView.vue'),
      },
      {
        path: 'scheduled-tasks',
        name: 'scheduled-tasks',
        component: () => import('@/views/scheduled-tasks/ScheduledTasksView.vue'),
      },
      {
        path: 'trash',
        name: 'trash',
        component: () => import('@/views/trash/FailedMessagesView.vue'),
      },
      {
        path: 'logs',
        name: 'logs',
        component: () => import('@/views/logs/LogsView.vue'),
      },
      {
        path: 'applications',
        name: 'applications',
        component: () => import('@/views/applications/ApplicationsView.vue'),
      },
      {
        path: 'settings',
        name: 'settings',
        component: () => import('@/views/settings/SettingsView.vue'),
      },
      {
        path: 'topologies',
        component: () => import('@/views/topologies/TopologiesLayout.vue'),
        children: [
          {
            path: '',
            name: 'topologies',
            component: () => import('@/views/topologies/TopologiesPlaceholder.vue'),
          },
          {
            path: ':id',
            name: 'topology-detail',
            component: () => import('@/views/topologies/TopologyDetailView.vue'),
            props: true,
          },
        ],
      },
      {
        path: 'users',
        name: 'users',
        component: () => import('@/views/users/UsersView.vue'),
      },
      {
        path: 'orchesty/account',
        name: 'account-settings',
        component: () => import('@/views/account/AccountSettingsView.vue'),
      },
    ],
  },
  {
    path: '/setup',
    name: 'setup',
    component: SetupView,
  },
  {
    path: '/sign-in',
    name: 'sign-in',
    component: SignInView,
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: ForgotPasswordView,
  },
  {
    path: '/reset-password/:token',
    name: 'reset-password',
    component: ResetPasswordView,
    props: true,
  },
  {
    path: '/accept-invite/:token',
    name: 'accept-invite',
    component: AcceptInviteView,
    props: true,
  },
]
