export default {
  getProcesses: {
    id: "DASHBOARD_PROCESSES",
    request: (data) => ({
      url: `progress?filter=${JSON.stringify(data)}`,
      method: "GET",
    }),
  },
}
