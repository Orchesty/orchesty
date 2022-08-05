import { Mutations } from "../../../types";
import { Alert, AlertsState } from "./state";
import { AlertsMutations } from "./types";

export const mutations: Mutations<AlertsMutations, AlertsState> = {
  add(state, payload: Alert) {
    const alertIndex = state.findIndex((alert) => alert.id === payload.id);
    if (alertIndex >= 0) {
      state[alertIndex] = {
        ...payload,
      };
    } else {
      state.push(payload);
    }
  },
  remove(state, payload: Alert["id"]) {
    const alertIndex = state.findIndex((alert) => alert.id === payload);
    state.splice(alertIndex, 1);
  },
};
