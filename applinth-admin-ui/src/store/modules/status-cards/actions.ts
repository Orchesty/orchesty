import { api } from "../../../api";
import {
  DashboardStatusQuery,
  DashboardStatusQueryVariables,
} from "../../../types/gqlGeneratedPrivate";
import { Actions } from "../../../types/vuex";
import { apiClient } from "../../../utils";
import { StatusCardsState } from "./state";
import { StatusCardsActions, StatusCardsMutations } from "./types";

export const actions: Actions<StatusCardsActions, StatusCardsState> = {
  async fetch({ commit }): Promise<StatusCardsState | undefined> {
    const { data } = await apiClient.callGraphqlPrivate<
      DashboardStatusQuery,
      DashboardStatusQueryVariables
    >({
      ...api.dashboard.status,
    });
    commit(StatusCardsMutations.Update, data?.dashboard);
    return data?.dashboard;
  },
};
