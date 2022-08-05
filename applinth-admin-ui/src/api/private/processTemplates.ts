import gql from "gql-tag";
import { ApiConfigs } from "../../types";

export type ProcessTemplatesApi =
  | "processTemplateList"
  | "createProcessTemplate"
  | "updateProcessTemplate"
  | "deleteProcessTemplate";

export const processTemplates: ApiConfigs<ProcessTemplatesApi> = {
  createProcessTemplate: {
    id: "CREATE_PROCESS_TEMPLATE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation CreateProcessTemplate($input: CreateProcessTemplateInput!) {
            createProcessTemplate(input: $input) {
              id
              name
              order
              processSubCodeIds
              processSubCodes {
                id
                name
                code
              }
            }
          }
        `,
      },
    }),
  },

  updateProcessTemplate: {
    id: "UPDATE_PROCESS_TEMPLATE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation UpdateProcessTemplate(
            $id: Int!
            $input: UpdateProcessTemplateInput!
          ) {
            updateProcessTemplate(id: $id, input: $input) {
              id
              name
              order
              processSubCodeIds
              processSubCodes {
                id
                name
                code
              }
            }
          }
        `,
      },
    }),
  },

  deleteProcessTemplate: {
    id: "DELETE_PROCESS_TEMPLATE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation DeleteProcessTemplate($id: Int!) {
            deleteProcessTemplate(id: $id)
          }
        `,
      },
    }),
  },

  processTemplateList: {
    id: "PROCESS_TEMPLATE_LIST",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          query ProcessTemplateList($input: ProcessTemplatesInput) {
            processTemplates(input: $input) {
              items {
                id
                name
                order
                processSubCodeIds
                processSubCodes {
                  id
                  name
                  code
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
};
