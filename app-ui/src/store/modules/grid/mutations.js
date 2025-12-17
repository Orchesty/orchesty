import { GRID } from "./types"
import createState from "./state"
import { resetState } from "@/store/utils"
export default {
  [GRID.MUTATIONS.GRID_RESPONSE]: (state, payload) => {
    state.filter = payload.filter
    state.sorter = payload.sorter
    state.paging = payload.paging
    state.search = payload.search
    state.items = payload.items
  },
  [GRID.MUTATIONS.RESET]: (state) => {
    resetState(state, createState(state.namespace, { ...state.backup }))
  },
}
