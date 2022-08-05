import { Getters } from "../../../types";
import { StatusCardsState } from "./state";
import { StatusCardsGetters } from "./types";

export const getters: Getters<StatusCardsGetters, StatusCardsState> = {
  getState(state): StatusCardsState {
    return state;
  },
};
