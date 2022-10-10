import AppLayout from '@/components/layout/sidebar/SidebarLayout'
import { ROUTES, SECURITY } from '@/services/enums/routerEnums'
import { TOPOLOGY, APP_STORE } from '@/services/enums/routerEnums'
import UserTaskTab from '@/components/app/topology/tabs/UserTaskTab'
import OverviewTab from '@/components/app/topology/tabs/OverviewTab'
import BpmnChart from '@/components/app/topology/tabs/ViewerTab'
import StatisticTab from '@/components/app/topology/tabs/StatisticTab'
import LogsTab from '@/components/app/topology/tabs/LogsTab'
import AvailableAppsGridHandler from '@/components/app/appStore/availableApp/AvailableAppsGridHandler'
import InstalledAppsGridHandler from '@/components/app/appStore/installedApp/InstalledAppsGridHandler'
import InstalledApp from '@/components/app/appStore/installedApp/InstalledApp'
import AvailableApp from '@/components/app/appStore/availableApp/AvailableApp'

export default [
  {
    path: '/',
    component: AppLayout,
    children: [
      {
        path: '',
        redirect: 'dashboard',
      },
      {
        path: 'dashboard',
        name: ROUTES.DASHBOARD,
        component: () => import('../../views/app/DashboardPage'),
        meta: { title: 'Dashboard' },
      },
      {
        path: 'editor',
        name: ROUTES.EDITOR,
        component: () => import('../../views/app/EditorPage'),
        meta: { title: 'Editor' },
      },
      // {
      //   path: 'health-check',
      //   name: ROUTES.HEALTH_CHECK,
      //   component: () => import('../../views/app/HealthCheckPage'),
      //   meta: { title: 'Health Check' },
      // },
      {
        path: 'logs',
        name: ROUTES.LOGS,
        component: () => import('../../views/app/LogPage'),
        meta: { title: 'Logs' },
      },
      {
        path: 'topology',
        name: ROUTES.TOPOLOGY.DEFAULT,
        component: () => import('../../views/app/TopologyPage'),
        meta: { title: 'Topology' },
        children: [
          {
            path: ':id/overview',
            name: TOPOLOGY.OVERVIEW,
            component: OverviewTab,
            meta: { title: 'Topology - Overview' },
          },
          {
            path: ':id/viewer',
            name: TOPOLOGY.VIEWER,
            component: BpmnChart,
            meta: { title: 'Topology - BPMN Chart' },
          },
          {
            path: ':id/statistic',
            name: TOPOLOGY.STATISTIC,
            component: StatisticTab,
            meta: { title: 'Topology - Statistics' },
          },
          {
            path: ':id/userTask',
            name: TOPOLOGY.USER_TASK,
            component: UserTaskTab,
            meta: { title: 'Topology - User Tasks' },
            children: [
              {
                path: ':userTaskId',
                component: UserTaskTab,
                name: TOPOLOGY.USER_TASK_DETAIL,
                meta: { title: 'Topology - User Tasks Detail' },
              },
            ],
          },
          {
            path: ':id/topology-logs',
            name: TOPOLOGY.LOGS,
            component: LogsTab,
            meta: { title: 'Topology - Logs' },
          },
        ],
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
        children: [
          {
            path: '',
            name: APP_STORE.DEFAULT,
            redirect: { name: APP_STORE.AVAILABLE_APPS },
          },
          {
            path: 'available-apps',
            name: APP_STORE.AVAILABLE_APPS,
            component: AvailableAppsGridHandler,
            meta: { title: 'Available Apps' },
          },
          {
            path: 'installed-apps',
            name: APP_STORE.INSTALLED_APPS,
            component: InstalledAppsGridHandler,
            meta: { title: 'Installed Apps' },
          },
        ],
      },
      {
        path: 'installed-app/:key',
        component: InstalledApp,
        name: APP_STORE.INSTALLED_APP,
        meta: { title: 'Installed App' },
      },
      {
        path: 'app-detail/:key',
        component: AvailableApp,
        name: APP_STORE.DETAIL_APP,
        meta: { title: 'Detail App' },
      },
      {
        path: 'services',
        name: ROUTES.IMPLEMENTATION,
        component: () => import('../../views/app/ImplementationPage'),
        meta: { title: 'Services' },
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
    component: () => import('../../views/auth/LoginPage'),
    meta: {
      auth: SECURITY.PUBLIC,
    },
  },
  {
    path: '/forgot-password',
    name: ROUTES.FORGOT_PASSWORD,
    component: () => import('../../views/auth/ForgotPasswordPage'),
    meta: {
      auth: SECURITY.PUBLIC,
    },
  },
  {
    path: '/forgot-password-sent',
    name: ROUTES.FORGOT_PASSWORD_SENT,
    component: () => import('../../views/auth/ForgotPasswordSentPage'),
    meta: {
      auth: SECURITY.PUBLIC,
    },
  },
  {
    path: '/restore-password/:token',
    name: ROUTES.RESTORE_PASSWORD,
    component: () => import('../../views/auth/ResetPasswordPage'),
    meta: {
      auth: SECURITY.PUBLIC,
    },
  },
  {
    path: '/register/:token',
    name: ROUTES.REGISTER,
    component: () => import('../../views/auth/RegistrationConfirmPage'),
    meta: {
      auth: SECURITY.PUBLIC,
    },
  },
  {
    path: '/password-changed',
    name: ROUTES.PASSWORD_CHANGED,
    component: () => import('../../views/auth/PasswordChangedPage'),
    meta: {
      auth: SECURITY.PUBLIC,
    },
  },
  {
    path: '*',
    name: ROUTES.NOT_FOUND,
    component: () => import('../../views/notFound/NotFoundPage'),
    meta: {
      auth: SECURITY.PUBLIC,
    },
  },
]
