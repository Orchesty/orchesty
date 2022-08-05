import { Mutations } from "../../../types";
import { StatusCardsState } from "./state";
import { StatusCards, StatusCardsMutations } from "./types";

export const mutations: Mutations<StatusCardsMutations, StatusCardsState> = {
  update(state, payload: StatusCards) {
    state.tickets = payload.tickets;
    state.laborers = payload.laborers;
    state.conflicts = payload.conflicts;
    state.pinnedTickets = payload.pinnedTickets;
    state.urgentTickets = payload.urgentTickets;
  },
};
