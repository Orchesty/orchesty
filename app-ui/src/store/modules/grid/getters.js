import { GRID } from "@/store/modules/grid/types"

export default {
  [GRID.GETTERS.GET_PAGING]: (state) => {
    return state.paging
  },
  [GRID.GETTERS.GET_SORTER]: (state) => {
    return state.sorter
  },
  [GRID.GETTERS.GET_FILTER]: (state) => {
    return state.filter
  },
}
