import gql from "gql-tag";
import { ApiConfigs } from "../../types";

export type RegularMaintenancesApi =
  | "regularMaintenanceList"
  | "createRegularMaintenance"
  | "updateRegularMaintenance"
  | "deleteRegularMaintenance";

const RegularMaintenanceForm = gql`
  fragment RegularMaintenanceForm on RegularMaintenance {
    id
    name
    number
    frequency
    deviceId
    device {
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
`;

// TODO filter value doesnt work with creating. update creates new record instead of altering the current one
const RegularMaintenanceFormK = gql`
  fragment RegularMaintenanceForm on RegularMaintenance {
    id
    name
    number
    frequency
    deviceId
    device {
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
`;

export const regularMaintenances: ApiConfigs<RegularMaintenancesApi> = {
  createRegularMaintenance: {
    id: "CREATE_REGULAR_MAINTENANCE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation CreateRegularMaintenance(
            $input: CreateRegularMaintenanceInput!
          ) {
            createRegularMaintenance(input: $input) {
              id
              ...RegularMaintenanceForm
            }
          }
          ${RegularMaintenanceForm}
        `,
      },
    }),
  },

  updateRegularMaintenance: {
    id: "UPDATE_REGULAR_MAINTENANCE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation UpdateRegularMaintenance(
            $id: Int!
            $input: UpdateRegularMaintenanceInput!
          ) {
            updateRegularMaintenance(id: $id, input: $input) {
              id
              ...RegularMaintenanceForm
            }
          }
          ${RegularMaintenanceFormK}
        `,
      },
    }),
  },

  deleteRegularMaintenance: {
    id: "DELETE_REGULAR_MAINTENANCE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation DeleteRegularMaintenance($id: Int!) {
            deleteRegularMaintenance(id: $id)
          }
        `,
      },
    }),
  },

  regularMaintenanceList: {
    id: "REGULAR_MAINTENANCE_LIST",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          query RegularMaintenanceList($input: RegularMaintenancesInput) {
            regularMaintenances(input: $input) {
              items {
                id
                ...RegularMaintenanceForm
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
          ${RegularMaintenanceForm}
        `,
      },
    }),
  },
};
