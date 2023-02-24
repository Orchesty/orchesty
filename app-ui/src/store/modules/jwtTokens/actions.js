import { callApi } from "@/store/utils"
import { API } from "@/api"
import { JWT_TOKENS } from "@/store/modules/jwtTokens/types"
import { addSuccessMessage } from "@/services/utils/flashMessages"

export default {
  [JWT_TOKENS.ACTIONS.FETCH_LIST]: async ({ dispatch, commit }) => {
    try {
      const data = await callApi(dispatch, {
        requestData: { ...API.jwtTokens.grid },
      })

      commit(JWT_TOKENS.MUTATIONS.SET_TOKENS, data)
      return true
    } catch {
      return false
    }
  },
  [JWT_TOKENS.ACTIONS.CREATE]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.jwtTokens.create },
        params: {
          ...payload,
        },
      })

      addSuccessMessage(
        dispatch,
        API.jwtTokens.create.id,
        "flashMessages.jwtTokenCreated"
      )

      return true
    } catch {
      return false
    }
  },
  [JWT_TOKENS.ACTIONS.DELETE]: async ({ dispatch }, payload) => {
    try {
      await callApi(dispatch, {
        requestData: { ...API.jwtTokens.delete },
        params: {
          ...payload,
        },
      })

      addSuccessMessage(
        dispatch,
        API.jwtTokens.delete.id,
        "flashMessages.jwtTokenDeleted"
      )

      return true
    } catch {
      return false
    }
  },
}
