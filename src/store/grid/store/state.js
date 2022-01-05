import { DIRECTION, createTableKey, createHeaderKey } from '../utils'

const VERSION = '1.0.0'

export const createDefaultGridState = (namespace, defaultState = {}) => {
  return {
    version: VERSION,
    namespace,
    default: defaultState,
    // updatable fields
    filter: defaultState.filter || [],
    native: null,
    filterMeta: {},
    sorter: Object.prototype.hasOwnProperty.call(defaultState, 'sorter')
      ? defaultState.sorter
      : [
          {
            column: 'id',
            direction: DIRECTION.DESCENDING,
          },
        ],
    paging: {
      total: 0,
      nextPage: 0,
      previousPage: 0,
      lastPage: 0,
      // updatable fields
      page: 1,
      itemsPerPage: 10,
      ...defaultState.paging,
    },
    search: null,
    items: [],
    headersMeta: [],
  }
}

export default (namespace, defaultState) => {
  let gridState = JSON.parse(localStorage.getItem(createTableKey(namespace)))
  let gridHeaderState = JSON.parse(localStorage.getItem(createHeaderKey(namespace)))

  let state = {}

  if (gridState && gridState.version === VERSION) {
    state = {
      ...gridState,
    }
  }

  if (gridHeaderState && gridHeaderState.version === VERSION) {
    state = {
      ...state,
      ...gridHeaderState,
    }
  }

  return Object.assign(createDefaultGridState(namespace, defaultState), state)
}
