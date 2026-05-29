import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { checkUsersExist } from '@/services/authService'
import { coreRoutes } from '@/config/routes'

let usersExistCache: boolean | null = null

export function createAppRouter(routes?: RouteRecordRaw[]) {
  const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: routes ?? coreRoutes,
  })

  router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore()

    const publicRoutes = ['/sign-in', '/setup', '/forgot-password']
    const isPublicRoute =
      publicRoutes.includes(to.path) ||
      to.path.startsWith('/reset-password') ||
      to.path.startsWith('/accept-invite')
    const requiresAuth = !isPublicRoute

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

  return router
}

export function invalidateUsersExistCache() {
  usersExistCache = null
}
