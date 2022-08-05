import gql from "gql-tag";
import { ApiConfigs } from "../../types";

export type DevicesApi =
  | "deviceList"
  | "createDevice"
  | "updateDevice"
  | "deleteDevice";

const DeviceForm = gql`
  fragment DeviceForm on Device {
    number
    name
    note
    laborerId
  }
`;

export const devices: ApiConfigs<DevicesApi> = {
  createDevice: {
    id: "CREATE_DEVICE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation CreateDevice($input: DeviceInput!) {
            createDevice(input: $input) {
              id
              ...DeviceForm
              laborerId
              laborer {
                firstname
                surname
              }
            }
          }
          ${DeviceForm}
        `,
      },
    }),
  },

  updateDevice: {
    id: "UPDATE_DEVICE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation UpdateDevice($id: Int!, $input: DeviceInput!) {
            updateDevice(id: $id, input: $input) {
              id
              ...DeviceForm
              laborerId
              laborer {
                firstname
                surname
              }
            }
          }
          ${DeviceForm}
        `,
      },
    }),
  },

  deleteDevice: {
    id: "DELETE_DEVICE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation DeleteDevice($id: Int!) {
            deleteDevice(id: $id)
          }
        `,
      },
    }),
  },

  deviceList: {
    id: "DEVICE_LIST",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          query DeviceList($input: DevicesInput) {
            devices(input: $input) {
              items {
                id
                ...DeviceForm
                laborerId
                laborer {
                  firstname
                  surname
                }
              }
              filter {
                filter {
                  column
                  operator
                  values
                }
              }
              sorter {
                column
                direction
              }
              pager {
                page
                size
                prev
                next
                last
                total
              }
              search
            }
          }
          ${DeviceForm}
        `,
      },
    }),
  },
};
