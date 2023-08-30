import { DIRECTION } from '@/services/enums/gridEnums'

const initialSorter = [
  {
    column: 'id',
    direction: DIRECTION.DESCENDING,
  },
]

const initialPaging = {
  total: 0,
  nextPage: 0,
  previousPage: 0,
  lastPage: 0,
  page: 1,
  itemsPerPage: 10,
}

export const createDefaultGridState = (namespace, defaultState = {}) => {
  return {
    namespace,
    filter: defaultState.filter || [],
    filterMeta: {},
    sorter: defaultState.sorter || initialSorter,
    paging: defaultState.paging || initialPaging,
    search: null,
    items: [],
    backup: defaultState,
  }
}

export default (namespace, defaultState) => {
  return Object.assign(createDefaultGridState(namespace, defaultState))
}
