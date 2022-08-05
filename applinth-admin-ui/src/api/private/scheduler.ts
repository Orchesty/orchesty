import gql from "gql-tag";
import { ApiConfigs } from "../../types";

export type SchedulerApi = "calendar";

export const scheduler: ApiConfigs<SchedulerApi> = {
  calendar: {
    id: "CALENDAR",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          query Calendar($input: CalendarInput!) {
            calendar(input: $input) {
              date
              conflicts
              laborerIds
              laborers {
                id
                state
                status
                firstname
                surname
              }
            }
          }
        `,
      },
    }),
  },
};
