import { IndexedApplicationDetail, Mutations } from "@/types"
import { ApplicationsMutations } from "@/store/modules/applications/types"
import { ApplicationsState } from "@/store/modules/applications/state"

export const mutations: Mutations<ApplicationsMutations, ApplicationsState> = {
  fetchingApplicationsMetadata(state, payload: boolean) {
    state.fetchingApplicationsMetadata = payload
  },
  setApplicationsMetadata(state, payload: IndexedApplicationDetail) {
    state.applicationsMetadata = payload
  },
}
