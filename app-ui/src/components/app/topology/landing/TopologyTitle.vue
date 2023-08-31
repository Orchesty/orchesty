<template>
  <v-col cols="12" md="7">
    <div class="d-flex" style="height: 100%">
      <span class="headline my-auto font-weight-bold truncate" style="max-width: 100%">
        {{ topologyActive.name }}
      </span>
    </div>
  </v-col>
</template>

<script>
import { mapGetters } from 'vuex'
import { TOPOLOGIES } from '@/store/modules/topologies/types'

export default {
  name: 'TopologyTitle',
  computed: {
    ...mapGetters(TOPOLOGIES.NAMESPACE, { topologyActive: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY }),
  },
}
</script>

<style scoped></style>
