export default {
  grid: {
    id: "GET_LOGS_ITEMS",
    urlPattern: "/logs",
    request: ({ paging, sorter }) => ({
      url: `/logs?filter=${JSON.stringify({ paging, sorter })}`,
      method: "GET",
    }),
  },
}
