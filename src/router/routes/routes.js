import App from '../../views/AppRouter'
import topologiesRouter, { Topology } from './childrenRoutes/topology'
import appStoreRouter from './childrenRoutes/appStore'
import { ROUTES, SECURITY } from './index'

export default [
  {
    path: '/',
    component: App,
    children: [
      {
        path: '',
        redirect: 'topologies',
      },
      {
        path: 'topologies',
        name: Topology.DEFAULT,
        component: () => import('../../views/app/DashboardPage'),
        meta: { title: 'Topologies' },
      },
      {
        path: 'editor-page',
        name: ROUTES.EDITOR_PAGE,
        component: () => import('../../views/app/EditorPage'),
        meta: { title: 'Editor' },
      },
      {
        path: 'logs',
        name: ROUTES.LOGS,
        component: () => import('../../views/app/LogPage'),
        meta: { title: 'Logs' },
      },
      {
        path: 'topologies',
        component: () => import('../../views/app/TopologyPage'),
        children: topologiesRouter,
      },
      {
        path: 'notifications',
        name: ROUTES.NOTIFICATIONS,
        component: () => import('../../views/app/NotificationPage'),
        meta: { title: 'Notifications' },
      },
      {
        path: 'user-tasks',
        name: ROUTES.USER_TASK,
        component: () => import('../../views/app/UserTaskPage'),
        meta: { title: 'User Tasks' },
      },
      {
        path: 'scheduled-tasks',
        name: ROUTES.SCHEDULED_TASK,
        component: () => import('../../views/app/ScheduledTaskPage'),
        meta: { title: 'Scheduled Tasks' },
      },
      {
        path: 'app-store',
        component: () => import('../../views/app/AppStorePage'),
        children: appStoreRouter,
      },
      {
        path: 'implementations',
        name: ROUTES.IMPLEMENTATIONS,
        component: () => import('../../views/app/ImplementationPage'),
        meta: { title: 'Implementations' },
      },
      {
        path: 'trash',
        name: ROUTES.TRASH,
        component: () => import('../../views/app/TrashPage'),
        meta: { title: 'Trash' },
        children: [
          {
            path: ':trashId',
            component: () => import('../../views/app/TrashPage'),
            name: ROUTES.TRASH_DETAIL,
            meta: { title: 'Trash - Detail' },
          },
        ],
      },
      {
        path: 'users',
        name: ROUTES.USERS,
        component: () => import('../../views/app/UsersPage'),
        meta: { title: 'Users' },
      },
      {
        path: 'profile',
        name: ROUTES.USER_PROFILE,
        component: () => import('../../views/app/UserProfilePage'),
        meta: { title: 'Profile' },
      },
    ],
  },
  {
    path: '/login',
    name: ROUTES.LOGIN,
    component: () => import('../../views/LoginPage'),
    meta: {
      auth: SECURITY.PUBLIC,
    },
  },
  {
    path: '/forgot-restorePassword',
    name: ROUTES.FORGOT_PASSWORD,
    component: () => import('../../views/ForgotPasswordPage'),
    meta: {
      auth: SECURITY.PUBLIC,
    },
  },
  {
    path: '/restore-restorePassword/:token',
    name: ROUTES.RESTORE_PASSWORD,
    component: () => import('../../views/RestorePasswordPage'),
    meta: {
      auth: SECURITY.PUBLIC,
    },
  },
  {
    path: '/registration-confirm/:token',
    name: ROUTES.REGISTER_PASSWORD,
    component: () => import('../../views/RegistrationConfirmPage'),
    meta: {
      auth: SECURITY.PUBLIC,
    },
  },
  {
    path: '/restorePassword-changed',
    name: ROUTES.PASSWORD_CHANGED,
    component: () => import('../../views/PasswordChangedPage'),
    meta: {
      auth: SECURITY.PUBLIC,
    },
  },
  {
    path: '*',
    name: ROUTES.NOT_FOUND,
    component: () => import('../../views/NotFoundPage'),
    meta: {
      auth: SECURITY.PUBLIC,
    },
  },
]
