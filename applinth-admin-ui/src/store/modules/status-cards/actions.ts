import { Actions } from "../../../types/vuex";
import { StatusCardsState } from "./state";
import { StatusCardsActions } from "./types";

export const actions: Actions<StatusCardsActions, StatusCardsState> = {
  async fetch({ commit }): Promise<StatusCardsState | undefined> {
    // TODO call backend API
    // const { data } = await apiClient.callGraphqlPrivate<
    //   DashboardStatusQuery,
    //   DashboardStatusQueryVariables
    // >({
    //   ...api.dashboard.status,
    // });
    // commit(StatusCardsMutations.Update, data?.dashboard);
    // return data?.dashboard;
    return new Promise(() => null);
  },
};
