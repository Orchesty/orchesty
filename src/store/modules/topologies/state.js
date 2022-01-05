export default () => ({
  topology: JSON.parse(localStorage.getItem('topology')) || null,
  topologies: [],
  statistics: null,
  nodes: null,
  nodeNames: [],
  dashboard: null,
})
