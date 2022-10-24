export default {
  getProcesses: {
    id: "DASHBOARD_PROCESSES",
    request: () => ({
      url: `progress`,
      method: "GET",
    }),
  },
}
