<template>
  <content-tabs v-if="isTopologyOpened()" is-topology :tabs="tabs" />
  <topology-no-selection v-else />
</template>

<script>
import ContentTabs from '@/components/layout/content/ContentTabs'
import { ROUTES } from '@/services/enums/routerEnums'
import { mapGetters } from 'vuex'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import TopologyNoSelection from '@/components/app/topology/landing/TopologyNoSelection'
import { TOPOLOGY_ENUMS } from '@/services/enums/topologyEnums'

export default {
  components: { TopologyNoSelection, ContentTabs },
  name: 'TopologyPage',
  computed: {
    ...mapGetters(TOPOLOGIES.NAMESPACE, { topologyActive: TOPOLOGIES.GETTERS.GET_ACTIVE_TOPOLOGY }),
  },
  methods: {
    isTopologyOpened() {
      return this.topologyActive?._id && this.topologyActive?.type !== TOPOLOGY_ENUMS.CATEGORY
    },
  },
  data() {
    return {
      TOPOLOGY_ENUMS,
      tabs: [
        {
          name: 'navigation.overview',
          route: ROUTES.TOPOLOGY.VIEWER,
        },
        {
          name: 'navigation.processes',
          route: ROUTES.TOPOLOGY.OVERVIEW,
        },
        {
          name: 'navigation.statistic',
          route: ROUTES.TOPOLOGY.STATISTIC,
        },
        {
          name: 'navigation.userTask',
          route: ROUTES.TOPOLOGY.USER_TASK,
        },
        {
          name: 'navigation.logs',
          route: ROUTES.TOPOLOGY.LOGS,
        },
      ],
    }
  },
}
</script>
