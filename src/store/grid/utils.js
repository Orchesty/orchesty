import { LOCAL_STORAGE } from '../../enums'

export const DIRECTION = {
  ASCENDING: 'ASC',
  DESCENDING: 'DESC',
}

export const FILTER_TYPE = {
  TEXT: 'TEXT',
  NUMBER: 'NUMBER',
  ENUMS: 'ENUMS',
  MULTIPLE_ENUMS: 'MULTIPLE_ENUMS',
  AUTO_COMPLETE: 'AUTO_COMPLETE',
  DATETIME: 'DATETIME',
  DATE: 'DATE',
  BOOLEAN: 'BOOLEAN',
  DATE_TIME_BETWEEN: 'DATE_TIME_BETWEEN',
  DATE_BETWEEN: 'DATE_BETWEEN',
}

export const FILTER = {
  QUICK_FILTER: 'QUICK_FILTER',
  SIMPLE_FILTER: 'SIMPLE_FILTER',
  ADVANCED_FILTER: 'ADVANCED_FILTER',
}

export const OPERATOR = {
  EQUAL: '`1`',
  NOT_EQUAL: 'NOT_EQUAL',
  LIKE: 'LIKE',
  STARTS_WITH: 'STARTS_WITH',
  ENDS_WITH: 'ENDS_WITH',
  EMPTY: 'EMPTY',
  NEMPTY: 'NEMPTY',
  GREATER_THAN: 'GREATER_THAN',
  LESS_THAN: 'LESS_THAN',
  GREATER_THAN_OR_EQUAL: 'GREATER_THAN_OR_EQUAL',
  LESS_THAN_OR_EQUAL: 'LESS_THAN_OR_EQUAL',
  BETWEEN: 'BETWEEN',
  IN: 'IN',
  NIN: 'NIN',
}

export const OPERATOR_CHAR = {
  [OPERATOR.EQUAL]: '=',
  [OPERATOR.NOT_EQUAL]: '!=',
  [OPERATOR.LIKE]: '%',
  [OPERATOR.STARTS_WITH]: '*%',
  [OPERATOR.ENDS_WITH]: '%*',
  [OPERATOR.EMPTY]: 'IS NULL',
  [OPERATOR.NEMPTY]: 'NOT NULL',
  [OPERATOR.GREATER_THAN]: '>',
  [OPERATOR.GREATER_THAN_OR_EQUAL]: '>=',
  [OPERATOR.LESS_THAN]: '<=',
  [OPERATOR.LESS_THAN_OR_EQUAL]: '>=',
  [OPERATOR.BETWEEN]: 'BETWEEN',
}

export const prepareSorter = (stateSorter, payloadSorter) => {
  if (payloadSorter === null) {
    return null
  }

  if (!Array.isArray(payloadSorter)) {
    throw new Error('Sorter must be array.')
  }

  if (payloadSorter[0].column === null && payloadSorter[0].direction === null) {
    return null
  }

  return payloadSorter
}

export const prepareGridData = (state, payload) => {
  state.filterMeta = payload && payload.filterMeta ? payload.filterMeta : state.filterMeta

  let gridData = {
    filter: state.filter ? [...state.filter] : [],
    sorter: state.sorter ? [...state.sorter] : null,
    paging: {
      page: state.paging.page,
      itemsPerPage: state.paging.itemsPerPage,
    },
    search: state.search,
    native: state.native || null,
  }

  if (state.native) {
    gridData.native = state.native
  }

  if (payload) {
    if (payload.filter) {
      gridData.filter = payload.filter
    }

    if (payload.sorter || payload.sorter === null) {
      const sorter = prepareSorter(gridData.sorter, payload.sorter)
      gridData.sorter = sorter !== null ? sorter : null
    }

    if (payload.paging) {
      gridData.paging = payload.paging
    }

    if (payload.search !== undefined) {
      gridData.search = payload.search
    }

    if (payload.native) {
      gridData.native = payload.native
    }

    // extra params for requests id, etc.
    gridData.params = payload.params
  }

  if (state.default.permanentFilter === true && state.default && state.default.filter && gridData.filter.length === 0) {
    gridData.filter = state.default.filter
  }

  return gridData
}

export const createTableKey = (namespace) => {
  return `${LOCAL_STORAGE.GRID}-${namespace}`
}

export const createHeaderKey = (namespace) => {
  return `${LOCAL_STORAGE.GRID_HEADER}-${namespace}`
}

export const prepareGridStateForSave = (state) => {
  return {
    version: state.version,
    filter: state.filter,
    filterMeta: state.filterMeta,
    sorter: state.sorter,
  }
}

export const prepareGridHeaderStateForSave = (state) => {
  return {
    version: state.version,
    headersMeta: state.headersMeta,
  }
}
