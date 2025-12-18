import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '@/views/HomeView.vue'
import SignInView from '@/views/auth/SignInView.vue'
import ForgotPasswordView from '@/views/auth/ForgotPasswordView.vue'
import ResetPasswordView from '@/views/auth/ResetPasswordView.vue'
import DashboardView from '@/views/dashboard/DashboardView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/dashboard',
    },
    {
      path: '/dashboard',
      name: 'dashboard',
      component: DashboardView,
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
      path: '/reset-password',
      name: 'reset-password',
      component: ResetPasswordView,
    },
    {
      path: '/home',
      name: 'home',
      component: HomeView,
    },
    {
      path: '/scheduled-tasks',
      name: 'scheduled-tasks',
      component: () => import('@/views/scheduled-tasks/ScheduledTasksView.vue'),
    },
    {
      path: '/trash',
      name: 'trash',
      component: () => import('@/views/trash/FailedMessagesView.vue'),
    },
    {
      path: '/logs',
      name: 'logs',
      component: () => import('@/views/logs/LogsView.vue'),
    },
    {
      path: '/applications',
      name: 'applications',
      component: () => import('@/views/applications/ApplicationsView.vue'),
    },
    {
      path: '/settings',
      name: 'settings',
      component: () => import('@/views/settings/SettingsView.vue'),
    },
    {
      path: '/topologies',
      name: 'topologies',
      component: () => import('@/views/topologies/TopologiesView.vue'),
    },
    {
      path: '/topologies/:id',
      name: 'topology-detail',
      component: () => import('@/views/topologies/TopologyDetailView.vue'),
      props: true,
    },
    {
      path: '/users',
      name: 'users',
      component: () => import('@/views/users/UsersView.vue'),
    },
    {
      path: '/audit-logs',
      name: 'audit-logs',
      component: () => import('@/views/audit-logs/AuditLogsView.vue'),
    },
  ],
})

export default router
