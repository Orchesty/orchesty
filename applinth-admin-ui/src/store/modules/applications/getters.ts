import { Getters, IndexedApplicationDetail } from "@/types"
import { ApplicationsState } from "@/store/modules/applications/state"
import { ApplicationsGetters } from "@/store/modules/applications/types"

export const getters: Getters<ApplicationsGetters, ApplicationsState> = {
  isFetchingApplicationsMetadata(state: ApplicationsState): boolean {
    return state.fetchingApplicationsMetadata
  },
  getApplicationsMetadata(state: ApplicationsState): IndexedApplicationDetail {
    return state.applicationsMetadata
  },
}
