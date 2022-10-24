import { IndexedApplicationDetail } from "@/types"

export interface ApplicationsState {
  fetchingApplicationsMetadata: boolean
  applicationsMetadata: IndexedApplicationDetail
}

export const createState = (): ApplicationsState => {
  return {
    fetchingApplicationsMetadata: false,
    applicationsMetadata: {},
  }
}
