<template>
  <v-col cols="12" md="5" sm="12">
    <div class="d-flex" style="height: 100%">
      <v-tooltip bottom>
        <template #activator="{ on, attrs }">
          <span class="headline font-weight-bold truncate my-auto max-w-100" v-bind="attrs" v-on="on">
            {{ topologyActive.name }}
          </span>
        </template>
        <span>{{ topologyActive.name }}</span>
      </v-tooltip>
      <span class="ml-3 my-auto text-lowercase" style="min-width: 70px">
        {{ $t('topologies.detail.version') }}: <strong class="font-weight-bold">{{ topologyActive.version }}</strong>
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
