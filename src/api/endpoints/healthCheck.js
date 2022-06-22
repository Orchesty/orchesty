export default {
  grid: {
    id: 'HEALTH_CHECK_GRID',
    request: () => ({
      url: '/metrics/consumers',
      method: 'GET',
    }),
    reduce: (data) => {
      return {
        items: data,
        paging: {
          page: 1,
          itemsPerPage: 99999999,
        },
        filter: [],
        sorter: null,
      }
    },
  },
  containers: {
    id: 'HEALTH_CHECK_CONTAINERS',
    request: () => ({
      url: '/metrics/containers',
      method: 'GET',
    }),
    reduce: (data) => {
      return {
        items: data,
        paging: {
          page: 1,
          itemsPerPage: 99999999,
        },
        filter: [],
        sorter: null,
      }
    },
  },
}
