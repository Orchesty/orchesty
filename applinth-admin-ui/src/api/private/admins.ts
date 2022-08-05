import gql from "gql-tag";
import { ApiConfigs } from "../../types";

export type AdminsApi =
  | "updateLoggedAdmin"
  | "updateLoggedAdminPassword"
  | "adminList"
  | "createAdmin"
  | "updateAdmin"
  | "deleteAdmin"
  | "admin";

const AdminForm = gql`
  fragment AdminForm on Admin {
    username
    firstname
    surname
    isSuperAdmin
  }
`;

export const admins: ApiConfigs<AdminsApi> = {
  updateLoggedAdmin: {
    id: "UPDATE_LOGGED_ADMIN",
    request: (variables) => ({
      method: "POST",
      data: {
        query: gql`
          mutation UpdateLoggedAdmin($input: UpdateLoggedAdminInput!) {
            updateLoggedAdmin(input: $input) {
              id
            }
          }
        `,
        variables,
      },
    }),
  },

  updateLoggedAdminPassword: {
    id: "UPDATE_LOGGED_ADMIN_PASSWORD",
    request: (variables) => ({
      method: "POST",
      data: {
        query: gql`
          mutation UpdateLoggedAdminPassword(
            $input: UpdateLoggedAdminPasswordInput!
          ) {
            updateLoggedAdminPassword(input: $input)
          }
        `,
        variables,
      },
    }),
  },

  createAdmin: {
    id: "CREATE_ADMIN",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation CreateAdmin($input: CreateAdminInput!) {
            createAdmin(input: $input) {
              id
              ...AdminForm
            }
          }
          ${AdminForm}
        `,
      },
    }),
  },

  updateAdmin: {
    id: "UPDATE_ADMIN",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation UpdateAdmin($id: Int!, $input: UpdateAdminInput!) {
            updateAdmin(id: $id, input: $input) {
              id
              ...AdminForm
            }
          }
          ${AdminForm}
        `,
      },
    }),
  },

  deleteAdmin: {
    id: "DELETE_ADMIN",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation DeleteAdmin($id: Int!) {
            deleteAdmin(id: $id)
          }
        `,
      },
    }),
  },

  admin: {
    id: "ADMIN",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          query Admin($id: Int!) {
            admin(id: $id) {
              id
              ...AdminForm
            }
          }
          ${AdminForm}
        `,
      },
    }),
  },

  adminList: {
    id: "ADMIN_LIST",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          query AdminList($input: AdminsInput) {
            admins(input: $input) {
              items {
                id
                ...AdminForm
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
          ${AdminForm}
        `,
      },
    }),
  },
};
