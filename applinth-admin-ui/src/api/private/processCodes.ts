import gql from "gql-tag";
import { ApiConfigs } from "../../types";

export type ProcessCodesApi =
  | "processCodeList"
  // | "createProcessCode"
  | "updateProcessCode";
// | "deleteProcessCode"

const ProcessCodeForm = gql`
  fragment ProcessCodeForm on ProcessCode {
    code
    processTemplate {
      id
    }
  }
`;

export const processCodes: ApiConfigs<ProcessCodesApi> = {
  // createProcessCode: {
  //   id: "CREATE_PROCESS_CODE",
  //   request: (variables) => ({
  //     method: "POST",
  //     data: {
  //       variables,
  //       query: gql`
  //         mutation CreateProcessCode($input: CreateProcessCodeInput!) {
  //           createProcessCode(input: $input) {
  //             ...ProcessCodeForm
  //           }
  //         }
  //         ${ProcessCodeForm}
  //       `,
  //     },
  //   }),
  // },

  updateProcessCode: {
    id: "UPDATE_PROCESS_CODE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation UpdateProcessCode(
            $code: String!
            $input: ProcessCodeInput!
          ) {
            updateProcessCode(code: $code, input: $input) {
              ...ProcessCodeForm
            }
          }
          ${ProcessCodeForm}
        `,
      },
    }),
  },

  // deleteProcessCode: {
  //   id: "DELETE_PROCESS_CODE",
  //   request: (variables) => ({
  //     method: "POST",
  //     data: {
  //       variables,
  //       query: gql`
  //         mutation DeleteProcessCode($id: Int!) {
  //           deleteProcessCode(id: $id)
  //         }
  //       `,
  //     },
  //   }),
  // },

  processCodeList: {
    id: "PROCESS_CODE_LIST",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          query ProcessCodeList($input: ProcessCodesInput) {
            processCodes(input: $input) {
              items {
                ...ProcessCodeForm
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
          ${ProcessCodeForm}
        `,
      },
    }),
  },
};
