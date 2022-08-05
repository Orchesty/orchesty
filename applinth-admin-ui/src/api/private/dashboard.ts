import gql from "gql-tag";
import { ApiConfigs } from "../../types";

export type DashboardApi = "status";

export const dashboard: ApiConfigs<DashboardApi> = {
  status: {
    id: "DASHBOARD_STATUS",
    request: () => ({
      method: "POST",
      data: {
        query: gql`
          query DashboardStatus {
            dashboard {
              tickets
              pinnedTickets
              urgentTickets
              laborers
              conflicts
            }
          }
        `,
      },
    }),
  },
};
