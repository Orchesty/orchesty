import gql from "gql-tag";
import { ApiConfigs } from "../../types";

export type LaborersApi =
  | "laborerList"
  | "laborer"
  | "invite"
  | "sync"
  | "updateLaborer"
  | "laborers";

export const laborers: ApiConfigs<LaborersApi> = {
  laborer: {
    id: "LABORER",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          query Laborer($id: Int!) {
            laborer(id: $id) {
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
        `,
      },
    }),
  },

  updateLaborer: {
    id: "UPDATE_LABORER",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation UpdateLaborer($id: Int!, $input: UpdateLaborerInput!) {
            updateLaborer(id: $id, input: $input) {
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
        `,
      },
    }),
  },

  invite: {
    id: "LABORER_INVITE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation InviteLaborer($input: InviteLaborerInput!, $id: Int!) {
            inviteLaborer(input: $input, id: $id)
          }
        `,
      },
    }),
  },

  sync: {
    id: "LABORERS_SYNC",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation SyncLaborers {
            syncLaborers
          }
        `,
      },
    }),
  },

  laborerList: {
    id: "LABORER_LIST",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          query LaborerList($input: LaborersInput) {
            laborers(input: $input) {
              items {
                id
                status
                firstname
                surname
                username
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

  laborers: {
    id: "LABORERS",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          query Laborers {
            laborers {
              items {
                id
                status
                firstname
                surname
                username
              }
            }
          }
        `,
      },
    }),
  },
};
