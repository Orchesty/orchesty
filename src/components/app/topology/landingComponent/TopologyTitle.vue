<template>
  <v-col cols="12" md="6" class="d-flex my-auto">
    <h1 class="headline">
      <span class="font-weight-bold text-capitalize">{{ topology.name }}&nbsp;</span>
      <span>v.{{ topology.version }}&nbsp;</span>
      <span :class="topologyStatusColor">{{ topologyStatus }} </span>
    </h1>
  </v-col>
</template>

<script>
import { TOPOLOGY_ENUMS } from '@/services/enums/topologyEnums'

export default {
  name: 'TopologyTitle',
  props: {
    topology: {
      type: Object,
      required: true,
    },
  },
  computed: {
    topologyStatusColor() {
      if (this.topology) {
        if (this.topology.visibility === TOPOLOGY_ENUMS.PUBLIC) {
          if (this.topology.enabled) {
            return 'green--text'
          } else {
            return 'red--text'
          }
        } else {
          return 'secondary--text'
        }
      } else {
        return ''
      }
    },
    topologyStatus() {
      if (this.topology) {
        if (this.topology.visibility === TOPOLOGY_ENUMS.PUBLIC) {
          if (this.topology.enabled) {
            return 'enabled'
          } else {
            return 'disabled'
          }
        } else {
          return TOPOLOGY_ENUMS.DRAFT
        }
      } else {
        return ''
      }
    },
  },
}
</script>

<style scoped></style>
