<template>
  <bpmn-i-o-viewer :topology="topologyData" />
</template>

<script>
import BpmnIOViewer from '@/components/app/bpmn/BpmnViewer'
import { mapState } from 'vuex'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
export default {
  name: 'BpmnChartHandler',
  data() {
    return {
      nodes: [],
      metrics: {},
      topologyData: JSON.parse(localStorage.getItem('topology')),
    }
  },
  computed: {
    ...mapState(TOPOLOGIES.NAMESPACE, ['topology']),
  },
  watch: {
    topology: {
      deep: true,
      handler(val) {
        this.topologyData = val
      },
    },
  },
  components: { BpmnIOViewer },
}
</script>

<style scoped></style>
