import UserTaskTab from '@/components/app/topology/tabs/UserTaskTab'

export const Topology = {
  DEFAULT: 'default',
  OVERVIEW: 'overview',
  EDITOR: 'editor',
  STATISTIC: 'statistic',
  USER_TASK: 'userTask',
  USER_TASK_DETAIL: 'userTaskDetail',
  LOGS: 'topology-logs',
}

export default [
  {
    path: ':id/overview',
    name: Topology.OVERVIEW,
    component: () => import('../../../components/app/topology/tabs/OverviewTab'),
    meta: { title: 'Topology - Overview' },
  },
  {
    path: ':id/editor',
    name: Topology.EDITOR,
    component: () => import('../../../components/app/topology/tabs/BpmnChart'),
    meta: { title: 'Topology - BPMN Chart' },
  },
  {
    path: ':id/statistic',
    name: Topology.STATISTIC,
    component: () => import('../../../components/app/topology/tabs/StatisticTab'),
    meta: { title: 'Topology - Statistics' },
  },
  {
    path: ':id/userTask',
    name: Topology.USER_TASK,
    component: () => import('../../../components/app/topology/tabs/UserTaskTab'),
    meta: { title: 'Topology - User Tasks' },
    children: [
      {
        path: ':userTaskId',
        component: UserTaskTab,
        name: Topology.USER_TASK_DETAIL,
        meta: { title: 'Topology - User Tasks Detail' },
      },
    ],
  },
  {
    path: ':id/topology-logs',
    name: Topology.LOGS,
    component: () => import('../../../components/app/topology/tabs/LogsTab'),
    meta: { title: 'Topology - Logs' },
  },
]
