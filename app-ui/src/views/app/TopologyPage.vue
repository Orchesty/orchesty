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
          name: 'topologies.detail.tabs.overview',
          route: ROUTES.TOPOLOGY.VIEWER,
        },
        {
          name: 'topologies.detail.tabs.processes',
          route: ROUTES.TOPOLOGY.OVERVIEW,
        },
        {
          name: 'topologies.detail.tabs.statistic',
          route: ROUTES.TOPOLOGY.STATISTIC,
        },
        {
          name: 'topologies.detail.tabs.userTask',
          route: ROUTES.TOPOLOGY.USER_TASK,
        },
        {
          name: 'topologies.detail.tabs.logs',
          route: ROUTES.TOPOLOGY.LOGS,
        },
      ],
    }
  },
}
</script>
