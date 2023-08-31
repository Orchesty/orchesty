export default {
  grid: {
    id: 'SCHEDULED_TASK_GRID',
    request: (data) => ({
      url: `/topologies/cron?filter=${JSON.stringify(data)}`,
      method: 'GET',
    }),
  },
}
