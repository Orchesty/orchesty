import gql from "gql-tag";
import { ApiConfigs } from "../../types";

export type DeviceAPI = "fetchDevice" | "cloneDevice";

export const device: ApiConfigs<DeviceAPI> = {
  fetchDevice: {
    id: "FETCH_DEVICE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          query Device($id: Int!) {
            device(id: $id) {
              id
              number
              name
              note
              laborerId
              laborer {
                id
                state
                status
                firstname
                surname
                ticketLimit
                isCadCam
              }
            }
          }
        `,
      },
    }),
  },
  cloneDevice: {
    id: "CLONE_DEVICE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation CopyDevice($id: Int!) {
            copyDevice(id: $id) {
              name
            }
          }
        `,
      },
    }),
  },
};
