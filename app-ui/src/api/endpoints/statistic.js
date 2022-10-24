export default {
  grid: {
    id: "STATISTIC_GRID",
    request: (data) => {
      return {
        url: `metrics/topology/${
          data.params.id
        }/requests?filter=${JSON.stringify(data)}`,
        method: "GET",
      }
    },
    reduce: (data) => {
      return {
        items: [data.items],
        paging: {
          ...data.paging,
        },
        filter: data.filter,
        sorter: data.sorter,
      }
    },
  },
  getList: {
    id: "STATISTIC_GET_LIST",
    request: ({ payload }) => {
      return {
        url: `metrics/topology/${payload.id}/requests?filter=${JSON.stringify(
          payload.settings || []
        )}`,
        method: "GET",
      }
    },
  },
}
