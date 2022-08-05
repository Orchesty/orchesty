import gql from "gql-tag";
import { ApiConfigs } from "../../types";

export type MaintenancesApi =
  | "maintenanceList"
  | "updateMaintenance"
  | "deleteMaintenance"
  | "maintenance"
  | "maintenanceAdvancedFilterData"
  | "createMaintenance"
  | "completeMaintenance";

const MaintenanceForm = gql`
  fragment MaintenanceForm on Maintenance {
    id
    type
    state
    plannedDate
    description
    responsiblePerson {
      id
      firstname
      surname
    }
    deviceId
    maintenanceDate
    report
    maintainedBy
    price
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

export const maintenances: ApiConfigs<MaintenancesApi> = {
  updateMaintenance: {
    id: "UPDATE_MAINTENANCE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation UpdateMaintenance(
            $id: Int!
            $input: UpdateMaintenanceInput!
          ) {
            updateMaintenance(id: $id, input: $input) {
              id
              ...MaintenanceForm
            }
          }
          ${MaintenanceForm}
        `,
      },
    }),
  },

  createMaintenance: {
    id: "CREATE_MAINTENANCE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation CreateMaintenance($input: CreateMaintenanceInput!) {
            createMaintenance(input: $input) {
              ...MaintenanceForm
            }
          }
          ${MaintenanceForm}
        `,
      },
    }),
  },

  deleteMaintenance: {
    id: "DELETE_MAINTENANCE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation DeleteMaintenance($id: Int!) {
            deleteMaintenance(id: $id)
          }
        `,
      },
    }),
  },

  maintenanceList: {
    id: "MAINTENANCE_LIST",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          query MaintenanceList($input: MaintenancesInput) {
            maintenances(input: $input) {
              items {
                id
                type
                state
                plannedDate
                description
                responsiblePerson {
                  id
                  firstname
                  surname
                }
                deviceId
                maintenanceDate
                report
                maintainedBy
                price
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
        `,
      },
    }),
  },
  maintenance: {
    id: "MAINTENANCE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          query Maintenance($id: Int!) {
            maintenance(id: $id) {
              id
              type
              state
              plannedDate
              description
              responsiblePersonId
              responsiblePerson {
                id
                firstname
                surname
                isSuperAdmin
                username
              }
              deviceId
              maintenanceDate
              report
              maintainedBy
              price
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
                  username
                  ticketLimit
                  isCadCam
                }
              }
            }
          }
        `,
      },
    }),
  },
  maintenanceAdvancedFilterData: {
    id: "MAINTENANCE_ADVANCED_FILTER_DATA",
    request: () => ({
      method: "POST",
      data: {
        query: gql`
          query {
            devices {
              items {
                id
                name
              }
            }
            laborers {
              items {
                id
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

  completeMaintenance: {
    id: "COMPLETE_MAINTENANCE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation CompleteMaintenance(
            $input: CompleteMaintenanceInput!
            $id: Int!
          ) {
            completeMaintenance(input: $input, id: $id) {
              id
            }
          }
        `,
      },
    }),
  },
};
