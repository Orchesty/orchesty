import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '@/views/HomeView.vue'
import SignInView from '@/views/auth/SignInView.vue'
import SetupView from '@/views/auth/SetupView.vue'
import ForgotPasswordView from '@/views/auth/ForgotPasswordView.vue'
import ResetPasswordView from '@/views/auth/ResetPasswordView.vue'
import AcceptInviteView from '@/views/auth/AcceptInviteView.vue'
import DashboardView from '@/views/dashboard/DashboardView.vue'
import { useAuthStore } from '@/stores/auth'
import { checkUsersExist } from '@/services/authService'

let usersExistCache: boolean | null = null

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
      path: '/users',
      name: 'users',
      component: () => import('@/views/users/UsersView.vue'),
    },
    {
      path: '/audit-logs',
      name: 'audit-logs',
      component: () => import('@/views/audit-logs/AuditLogsView.vue'),
    },
    {
      path: '/trace',
      name: 'trace',
      component: () => import('@/views/trace/TraceView.vue'),
    },
    {
      path: '/orchesty/account',
      name: 'account-settings',
      component: () => import('@/views/account/AccountSettingsView.vue'),
    },
  ],
})

// Navigation guard for authentication and setup detection
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  const publicRoutes = ['/sign-in', '/setup', '/forgot-password']
  const isPublicRoute = publicRoutes.includes(to.path) || to.path.startsWith('/reset-password') || to.path.startsWith('/accept-invite')
  const requiresAuth = !isPublicRoute

  // Resolve setup state on first navigation
  if (usersExistCache === null) {
    try {
      usersExistCache = await checkUsersExist()
    } catch {
      usersExistCache = true
    }
  }

  if (requiresAuth && !authStore.isAuthenticated) {
    authStore.initializeAuth()
  }

  if (requiresAuth && !authStore.isAuthenticated) {
    next(usersExistCache ? '/sign-in' : '/setup')
  } else if (to.path === '/sign-in' && !usersExistCache) {
    next('/setup')
  } else if (to.path === '/setup' && usersExistCache) {
    next('/sign-in')
  } else if (to.path === '/sign-in' && authStore.isAuthenticated) {
    next('/dashboard')
  } else {
    next()
  }
})

export function invalidateUsersExistCache() {
  usersExistCache = null
}

export default router
