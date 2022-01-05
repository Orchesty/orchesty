export default {
  grid: {
    id: 'OVERVIEW_GRID',
    request: (data) => ({
      url: `progress/topology/${data.params.id}?filter=${JSON.stringify(data)}`,
      method: 'GET',
    }),
  },
}
