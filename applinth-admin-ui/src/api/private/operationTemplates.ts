import gql from "gql-tag";
import { ApiConfigs } from "../../types";

export type OperationTemplatesApi =
  | "operationTemplateList"
  | "createOperationTemplate"
  | "updateOperationTemplate"
  | "deleteOperationTemplate";

const OperationTemplateForm = gql`
  fragment OperationTemplateForm on OperationTemplate {
    name
    value
    valueTwo
    minimumTime
    minimumTimeTwo
    comfortTime
    comfortTimeTwo
  }
`;

export const operationTemplates: ApiConfigs<OperationTemplatesApi> = {
  createOperationTemplate: {
    id: "CREATE_OPERATION_TEMPLATE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation CreateOperationTemplate(
            $input: CreateOperationTemplateInput!
          ) {
            createOperationTemplate(input: $input) {
              id
              ...OperationTemplateForm
            }
          }
          ${OperationTemplateForm}
        `,
      },
    }),
  },

  updateOperationTemplate: {
    id: "UPDATE_OPERATION_TEMPLATE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation UpdateOperationTemplate(
            $id: Float!
            $input: UpdateOperationTemplateInput!
          ) {
            updateOperationTemplate(id: $id, input: $input) {
              id
              ...OperationTemplateForm
            }
          }
          ${OperationTemplateForm}
        `,
      },
    }),
  },

  deleteOperationTemplate: {
    id: "DELETE_OPERATION_TEMPLATE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation DeleteOperationTemplate($id: Int!) {
            deleteOperationTemplate(id: $id)
          }
        `,
      },
    }),
  },

  operationTemplateList: {
    id: "OPERATION_TEMPLATE_LIST",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          query OperationTemplateList($input: OperationTemplatesInput) {
            operationTemplates(input: $input) {
              items {
                id
                ...OperationTemplateForm
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
          ${OperationTemplateForm}
        `,
      },
    }),
  },
};
