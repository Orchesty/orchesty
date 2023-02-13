import { callCustomApi } from "@/store/utils"
import { REQUESTS_STATE } from "@/store/modules/api/types"

export default {
  [REQUESTS_STATE.ACTIONS.CALL_CUSTOM_REQUEST]: async (
    { dispatch },
    payload
  ) => {
    try {
      return callCustomApi(dispatch, {
        requestData: {
          id: "APP_CALL_CUSTOM_API",
          request: () => ({
            url: payload.url,
            method: payload.method,
            data: payload.body,
          }),
        },
      })
    } catch (e) {
      return false
    }
  },
}
