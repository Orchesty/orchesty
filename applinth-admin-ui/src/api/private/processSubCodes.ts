import gql from "gql-tag";
import { ApiConfigs } from "../../types";

export type ProcessSubCodesApi =
  | "processSubCodeList"
  | "createProcessSubCode"
  | "updateProcessSubCode"
  | "deleteProcessSubCode";

const ProcessSubCodeForm = gql`
  fragment ProcessSubCodeForm on ProcessSubCode {
    name
    code
    operationTemplates {
      id
      name
    }
  }
`;

export const processSubCodes: ApiConfigs<ProcessSubCodesApi> = {
  createProcessSubCode: {
    id: "CREATE_PROCESS_SUB_CODE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation CreateProcessSubCode($input: CreateProcessSubCodeInput!) {
            createProcessSubCode(input: $input) {
              id
              ...ProcessSubCodeForm
            }
          }
          ${ProcessSubCodeForm}
        `,
      },
    }),
  },

  updateProcessSubCode: {
    id: "UPDATE_PROCESS_SUB_CODE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation UpdateProcessSubCode(
            $id: Int!
            $input: UpdateProcessSubCodeInput!
          ) {
            updateProcessSubCode(id: $id, input: $input) {
              id
              ...ProcessSubCodeForm
            }
          }
          ${ProcessSubCodeForm}
        `,
      },
    }),
  },

  deleteProcessSubCode: {
    id: "DELETE_PROCESS_SUB_CODE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation DeleteProcessSubCode($id: Int!) {
            deleteProcessSubCode(id: $id)
          }
        `,
      },
    }),
  },

  processSubCodeList: {
    id: "PROCESS_SUB_CODE_LIST",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          query ProcessSubCodeList($input: ProcessSubCodesInput) {
            processSubCodes(input: $input) {
              items {
                id
                ...ProcessSubCodeForm
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
          ${ProcessSubCodeForm}
        `,
      },
    }),
  },
};
