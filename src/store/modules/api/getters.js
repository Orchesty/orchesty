import { REQUESTS_STATE } from "./types"

const getState = (requests) => {
  let isSending = false
  let isError = false
  const errors = []
  requests.forEach((item) => {
    isSending = isSending ? true : item.isSending
    isError = isError ? true : item.isError

    if (isError) {
      errors.push(item.error)
    }
  })

  return {
    isSending,
    isError,
    errors,
  }
}

export default {
  [REQUESTS_STATE.GETTERS.GET_GLOBAL_ERRORS]: (state) => {
    return (
      Object.values(state.items).filter(
        (item) => item.isError && item.errorType === undefined
      ) || []
    )
  },
  [REQUESTS_STATE.GETTERS.GET_STATE]:
    (state) =>
    (ids = [], type) => {
      let requests = []
      if (!type) {
        requests =
          Object.values(state.items).filter((item) => ids.includes(item.id)) ||
          []
      } else {
        requests =
          Object.values(state.items).filter(
            (item) => ids.includes(item.id) && type === item.errorType
          ) || []
      }

      return getState(requests)
    },
}
