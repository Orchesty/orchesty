import gql from "gql-tag";
import { ApiConfigs } from "../../types";

export type TicketAPI =
  | "fetchTicket"
  | "solveTicketProblem"
  | "makeTicketProblem"
  | "changeDeadline"
  | "pin"
  | "unpin"
  | "makeImportant"
  | "makeNotImportant";

export const ticket: ApiConfigs<TicketAPI> = {
  fetchTicket: {
    id: "FETCH_STATUS",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          query Ticket($id: Int!) {
            ticket(id: $id) {
              isPinned
              important
              created
              problem
              id
              externalChat {
                message
                sent
                isRead
                sender {
                  firstname
                  surname
                }
                recipient {
                  firstname
                  surname
                }
                thumbnail {
                  id
                }
              }
              internalChat {
                message
                sent
                isRead
                sender {
                  firstname
                  surname
                }
                recipient {
                  firstname
                  surname
                }
                thumbnail {
                  id
                }
              }
              number
              important
              doctorNote
              laboratoryNote
              patientId
              processId
              doctorId
              macros {
                subCode
                items
                price
                name
              }
              externalId
              price
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
              process {
                id
                deadline
                operationsIds
                operations {
                  id
                  templateId
                  laborerId
                  conflict
                  status
                  skipped
                  template {
                    id
                    name
                    value
                    valueTwo
                    minimumTime
                    minimumTimeTwo
                    comfortTime
                    comfortTimeTwo
                    processSubCodeIds
                    processSubCodes {
                      id
                      name
                      code
                      isCadCam
                    }
                  }
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
              doctor {
                id
                fullName
                email
                phone
                companyName
                nameAddress
                street
                city
                postCode
              }
              patient {
                id
                firstname
                surname
                email
                phone
              }
              operations {
                id
                templateId
                laborerId
                conflict
                status
                estimateFrom
                estimateTo
                realFrom
                realTo
                skipped
                template {
                  id
                  name
                  value
                  valueTwo
                  minimumTime
                  minimumTimeTwo
                  comfortTime
                  comfortTimeTwo
                  processSubCodeIds
                  processSubCodes {
                    id
                    name
                    code
                    isCadCam
                  }
                }
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
          }
        `,
      },
    }),
  },

  changeDeadline: {
    id: "CHANGE_DEADLINE",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation ChangeDeadline($input: ChangeDeadlineInput!) {
            changeDeadline(input: $input)
          }
        `,
      },
    }),
  },

  solveTicketProblem: {
    id: "SOLVE_TICKET_STATUS",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation SolveTicketProblem($id: Int!) {
            solveTicketProblem(id: $id)
          }
        `,
      },
    }),
  },

  makeTicketProblem: {
    id: "MAKE_TICKET_STATUS",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation MakeTicketProblem($id: Int!) {
            makeTicketProblem(id: $id)
          }
        `,
      },
    }),
  },

  pin: {
    id: "TICKET_PIN",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation PinTicket($id: Int!) {
            pinTicket(id: $id)
          }
        `,
      },
    }),
  },

  unpin: {
    id: "TICKET_UNPIN",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation UnpinTicket($id: Int!) {
            unpinTicket(id: $id)
          }
        `,
      },
    }),
  },

  makeImportant: {
    id: "TICKET_MAKE_IMPORTANT",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation MakeTicketImportant($id: Int!) {
            makeTicketImportant(id: $id)
          }
        `,
      },
    }),
  },

  makeNotImportant: {
    id: "TICKET_MAKE_NOT_IMPORTANT",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          mutation MakeTicketNotImportant($id: Int!) {
            makeTicketNotImportant(id: $id)
          }
        `,
      },
    }),
  },
};
