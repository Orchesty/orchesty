export default {
  grid: {
    id: 'OVERVIEW_LIST',
    request: ({ paging, sorter }) => ({
      url: `/process/overview?filter=${JSON.stringify({ paging, sorter })}`,
      method: 'GET',
    }),
  },
}
