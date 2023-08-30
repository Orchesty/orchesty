import auth from './endpoints/auth'
import admin from './endpoints/user'
import topology from './endpoints/topology'
import implementation from './endpoints/implementation'
import scheduledTask from './endpoints/scheduledTask'
import userTask from './endpoints/userTask'
import notification from './endpoints/notification'
import appStore from './endpoints/appStore'
import folder from './endpoints/folder'
import statistic from './endpoints/statistic'
import overview from './endpoints/overview'
import trash from './endpoints/trash'
import healthCheck from './endpoints/healthCheck'

export const API = {
  auth,
  admin,
  overview,
  topology,
  implementation,
  scheduledTask,
  userTask,
  notification,
  appStore,
  folder,
  statistic,
  trash,
  healthCheck,
}
