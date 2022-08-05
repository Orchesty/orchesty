import gql from "gql-tag";
import { ApiConfigs } from "../../types";

export type AuthApi =
  | "login"
  | "refreshToken"
  | "setPassword"
  | "resetPassword";

export const Administrator = gql`
  fragment Administrator on Admin {
    username
    firstname
    surname
    isSuperAdmin
  }
`;

export const auth: ApiConfigs<AuthApi> = {
  login: {
    id: "LOGIN_AUTH",
    mock: {
      login: {
        accessToken: "abc",
        expiresIn: new Date().toISOString(),
        adminId: 1,
        admin: {
          username: "Login",
          firstname: "Jmeno",
          surname: "Prijmeni",
          isSuperAdmin: true,
        },
      },
    },
    request: (variables) => ({
      method: "POST",
      data: {
        query: gql`
          mutation Login($username: String!, $password: String!) {
            login(input: { username: $username, password: $password }) {
              accessToken
              expiresIn
              admin {
                ...Administrator
              }
              adminId
            }
          }
          ${Administrator}
        `,
        variables,
      },
    }),
  },
  refreshToken: {
    id: "REFRESH_TOKEN_AUTH",
    mock: {
      refreshToken: {
        accessToken: "abc",
        expiresIn: 10000,
        adminId: 1,
        admin: {
          username: "Login",
          firstname: "Jmeno",
          surname: "Prijmeni",
          isSuperAdmin: true,
        },
      },
    },
    request: () => ({
      method: "POST",
      data: {
        query: gql`
          mutation RefreshToken {
            refreshToken {
              accessToken
              expiresIn
              admin {
                ...Administrator
              }
              adminId
            }
          }
          ${Administrator}
        `,
      },
    }),
  },

  resetPassword: {
    id: "RESET_PASSWORD",
    request: (variables) => ({
      method: "POST",
      data: {
        query: gql`
          mutation ResetPassword($input: ResetPasswordInput!) {
            resetPassword(input: $input)
          }
        `,
        variables,
      },
    }),
  },

  setPassword: {
    id: "SET_PASSWORD",
    request: (variables) => ({
      method: "POST",
      data: {
        query: gql`
          mutation SetPassword($input: SetPasswordInput!) {
            setPassword(input: $input)
          }
        `,
        variables,
      },
    }),
  },
};
