export default {
  grid: {
    id: "OVERVIEW_GRID",
    request: ({ search, filter, paging, sorter, params }) => ({
      url: `progress/topology/${params.id}?filter=${JSON.stringify({
        search,
        filter,
        paging,
        sorter,
      })}`,
      method: "GET",
    }),
  },
}
