export default {
  grid: {
    id: 'TRASH_GRID',
    request: ({ paging, sorter, filter, native }) => ({
      url: `/user-task?filter=${JSON.stringify({ paging, sorter, filter, native })}`,
      method: 'GET',
    }),
  },
}
