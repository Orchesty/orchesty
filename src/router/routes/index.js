import { Topology } from './childrenRoutes/topology'
import { APP_STORE } from './childrenRoutes/appStore'

export const SECURITY = {
  PRIVATE: 'PRIVATE',
  PUBLIC: 'PUBLIC',
}

export const ROUTES = {
  USERS: 'users',
  TRASH: 'trash',
  TRASH_DETAIL: 'trashDetail',
  LOGS: 'logs',
  TOPOLOGIES: Topology,
  NOTIFICATIONS: 'notifications',
  EDITOR_PAGE: 'editor-page',
  USER_TASK: 'user-task',
  SCHEDULED_TASK: 'scheduled-task',
  IMPLEMENTATIONS: 'implementations',
  APP_STORE,
  USER_PROFILE: 'user-profile',
  LOGIN: 'login',
  REGISTER_PASSWORD: 'register-restorePassword',
  FORGOT_PASSWORD: 'forgot-restorePassword',
  RESTORE_PASSWORD: 'restore-restorePassword',
  PASSWORD_CHANGED: 'password-changed',
  NOT_FOUND: 'not-found',
}
