import gql from "gql-tag";
import { ApiConfigs } from "../../types";

export type TicketsApi = "ticketList" | "pinnedTicketList";

export const tickets: ApiConfigs<TicketsApi> = {
  pinnedTicketList: {
    id: "PINNED_TICKET_LIST",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          query PinnedTicketList($input: TicketsInput) {
            pinnedTickets(input: $input) {
              items {
                created
                id
                important
                number
                price
                problem
                urgent
                laborer {
                  firstname
                  surname
                }
                process {
                  deadline
                }
                doctor {
                  companyName
                }
                patient {
                  firstname
                  surname
                }
                operations {
                  status
                  laborer {
                    firstname
                    surname
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

  ticketList: {
    id: "TICKET_LIST",
    request: (variables) => ({
      method: "POST",
      data: {
        variables,
        query: gql`
          query TicketList($input: TicketsInput) {
            tickets(input: $input) {
              items {
                created
                id
                important
                isPinned
                number
                price
                problem
                urgent
                laborer {
                  firstname
                  surname
                }
                process {
                  deadline
                }
                doctor {
                  companyName
                }
                patient {
                  firstname
                  surname
                }
                operations {
                  status
                  laborer {
                    firstname
                    surname
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
};
