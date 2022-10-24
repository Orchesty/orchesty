import { Mutations } from "../../../types"
import { ApiState, RequestDetails } from "./state"
import { ApiMutations } from "./types"

export const mutations: Mutations<ApiMutations, ApiState> = {
  startSending(state, { id }: Pick<RequestDetails, "id">) {
    const requestDetails = state.find((item) => item.id === id)
    if (requestDetails) {
      requestDetails.error = ""
      requestDetails.isError = false
      requestDetails.isSending = true
    } else {
      state.push({
        id,
        isSending: true,
        isError: false,
        error: "",
      })
    }
  },
  stopSending(state, { id }: Pick<RequestDetails, "id">) {
    const requestDetails = state.find((item) => item.id === id)
    if (requestDetails) {
      requestDetails.isSending = false
    }
  },
  errorSending(state, { id, error }: Pick<RequestDetails, "id" | "error">) {
    const requestDetails = state.find((item) => item.id === id)
    if (requestDetails) {
      requestDetails.isSending = false
      requestDetails.error = error
      requestDetails.isError = true
    }
  },
}
