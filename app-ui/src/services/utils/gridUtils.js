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

    // extra params for requests id, etc.
    gridData.params = payload.params
  }

  return gridData
}
