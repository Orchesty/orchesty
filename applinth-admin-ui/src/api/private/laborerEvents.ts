import gql from "gql-tag";
import { ApiConfigs } from "../../types";

export type LaborerEventsApi =
  | "laborerEventList"
  | "createLaborerEvent"
  | "updateLaborerEvent"
  | "deleteLaborerEvent"
  | "export";

export const laborerEvents: ApiConfigs<LaborerEventsApi> = {
  createLaborerEvent: {
    id: "CREATE_LABORER_EVENT",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation CreateLaborerEvent($input: CreateLaborerEventInput!) {
            createLaborerEvent(input: $input) {
              id
            }
          }
        `,
      },
    }),
  },

  updateLaborerEvent: {
    id: "UPDATE_LABORER_EVENT",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation UpdateLaborerEvent($input: UpdateLaborerEventInput!) {
            updateLaborerEvent(input: $input) {
              ids {
                id
              }
            }
          }
        `,
      },
    }),
  },

  deleteLaborerEvent: {
    id: "DELETE_LABORER_EVENT",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation DeleteLaborerEvent($input: DeleteLaborerEventInput!) {
            deleteLaborerEvent(input: $input)
          }
        `,
      },
    }),
  },

  laborerEventList: {
    id: "LABORER_EVENT_LIST",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          query LaborerEventList($input: LaborerEventsInput) {
            laborerEvents(input: $input) {
              items {
                ids {
                  id
                }
                laborerId
                type
                fromDate
                toDate
                hours
                isHalfDay
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

  export: {
    id: "EXPORT_LABORER_EVENTS",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          query ExportLaborerEvents($input: ExportLaborerEventsInput!) {
            exportLaborerEvents(input: $input)
          }
        `,
      },
    }),
  },
};
