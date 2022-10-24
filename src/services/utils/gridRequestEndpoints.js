import { API } from "@/api"
import { DATA_GRIDS } from "@/services/enums/dataGridEnums"

export const GRID_REQUESTS = {
  [DATA_GRIDS.INSTALLED_APPS]: API.appStore.getInstalledApps,
  [DATA_GRIDS.USER_TASK]: API.userTask.grid,
  [DATA_GRIDS.ADMIN_USERS_LIST]: API.admin.getList,
  [DATA_GRIDS.OVERVIEW]: API.overview.grid,
  [DATA_GRIDS.STATISTICS]: API.statistic.grid,
  [DATA_GRIDS.SCHEDULED_TASK]: API.scheduledTask.grid,
  [DATA_GRIDS.IMPLEMENTATIONS_LIST]: API.implementation.grid,
  [DATA_GRIDS.AVAILABLE_APPS]: API.appStore.getAvailableApps,
  [DATA_GRIDS.LOGS]: API.topology.getLogs,
  [DATA_GRIDS.NODE_LOGS]: API.topology.getNodeLogsByID,
  [DATA_GRIDS.TOPOLOGY_LOGS]: API.topology.getLogsByID,
  [DATA_GRIDS.HEALTH_CHECK_QUEUES]: API.healthCheck.grid,
  [DATA_GRIDS.HEALTH_CHECK_CONTAINERS]: API.healthCheck.containers,
  [DATA_GRIDS.TRASH]: API.trash.grid,
}
