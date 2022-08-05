import { StatusCards } from "./types";

export type StatusCardsState = StatusCards;

export const createState = (): StatusCardsState => {
  return {
    tickets: 0,
    pinnedTickets: 0,
    urgentTickets: 0,
    laborers: 0,
    conflicts: 0,
  };
};
